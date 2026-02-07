<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Exports\SalesReportExport;
use App\Exports\PurchasesReportExport;
use App\Exports\StockReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function dashboard()
    {
        // Today's sales
        $todaySales = Sale::whereDate('sale_date', today())
            ->where('status', 'completed')
            ->sum('total');

        $todayTransactions = Sale::whereDate('sale_date', today())
            ->where('status', 'completed')
            ->count();

        // This month's sales
        $monthSales = Sale::whereYear('sale_date', date('Y'))
            ->whereMonth('sale_date', date('m'))
            ->where('status', 'completed')
            ->sum('total');

        // Low stock products
        $lowStockProducts = Product::whereRaw('
            (SELECT COALESCE(SUM(quantity), 0) FROM stocks WHERE product_id = products.id) < products.minimum_stock
        ')->count();

        // Total products
        $totalProducts = Product::where('is_active', true)->count();

        // Top selling products this month
        $topProducts = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereYear('sales.sale_date', date('Y'))
            ->whereMonth('sales.sale_date', date('m'))
            ->where('sales.status', 'completed')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sale_details.quantity) as total_sold'),
                DB::raw('SUM(sale_details.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get();

        // Recent sales
        $recentSales = Sale::with('warehouse')
            ->where('status', 'completed')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'today_sales' => $todaySales,
            'today_transactions' => $todayTransactions,
            'month_sales' => $monthSales,
            'low_stock_products' => $lowStockProducts,
            'total_products' => $totalProducts,
            'top_products' => $topProducts,
            'recent_sales' => $recentSales,
        ]);
    }

    public function salesReport(Request $request)
    {
        $query = Sale::with(['warehouse', 'user'])
            ->where('status', 'completed');

        if ($request->start_date) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $sales = $query->latest('sale_date')->get();

        $summary = [
            'total_sales' => $sales->count(),
            'total_revenue' => $sales->sum('total'),
            'total_discount' => $sales->sum('discount'),
            'total_tax' => $sales->sum('tax'),
        ];

        return response()->json([
            'sales' => $sales,
            'summary' => $summary,
        ]);
    }

    public function purchasesReport(Request $request)
    {
        $query = Purchase::with(['supplier', 'warehouse', 'user'])
            ->where('status', 'received');

        if ($request->start_date) {
            $query->whereDate('purchase_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('purchase_date', '<=', $request->end_date);
        }

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchases = $query->latest('purchase_date')->get();

        $summary = [
            'total_purchases' => $purchases->count(),
            'total_amount' => $purchases->sum('total'),
            'total_shipping' => $purchases->sum('shipping_cost'),
            'gross_amount' => $purchases->sum('subtotal'),
        ];

        return response()->json([
            'purchases' => $purchases,
            'summary' => $summary,
        ]);
    }

    public function stockReport(Request $request)
    {
        $query = Product::with(['category', 'baseUnit'])
            ->where('is_active', true);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('low_stock') && $request->low_stock) {
            $query->whereRaw('
                (SELECT COALESCE(SUM(quantity), 0) FROM stocks WHERE product_id = products.id) < products.minimum_stock
            ');
        }

        // Add stock info
        $query->withSum('stocks as total_stock', 'quantity');

        if ($request->warehouse_id) {
            $query->whereHas('stocks', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        $products = $query->get();

        $summary = [
            'total_products' => $products->count(),
            'total_stock_value' => $products->sum(function ($product) {
                return ($product->total_stock ?? 0) * $product->base_price;
            }),
            'low_stock_products' => $products->filter(function ($product) {
                return ($product->total_stock ?? 0) < $product->minimum_stock;
            })->count(),
        ];

        return response()->json([
            'products' => $products,
            'summary' => $summary,
        ]);
    }

    public function profitReport(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');

        // Get sales in period
        $salesData = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(sale_details.quantity) as quantity_sold'),
                DB::raw('SUM(sale_details.subtotal) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->get();

        // Calculate cost (simplified - using base price)
        $productProfit = $salesData->map(function ($item) {
            $product = Product::find($item->product_id);
            $cost = $product ? $item->quantity_sold * $product->base_price * 0.7 : 0; // Assume 70% cost
            $profit = $item->revenue - $cost;

            return [
                'product' => [
                    'id' => $item->product_id,
                    'name' => $item->product_name,
                ],
                'quantity_sold' => $item->quantity_sold,
                'revenue' => $item->revenue,
                'cost' => $cost,
                'profit' => $profit,
            ];
        });

        $totalRevenue = $productProfit->sum('revenue');
        $totalCost = $productProfit->sum('cost');
        $grossProfit = $totalRevenue - $totalCost;
        $profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

        $summary = [
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'profit_margin' => $profitMargin,
        ];

        return response()->json([
            'summary' => $summary,
            'product_profit' => $productProfit,
        ]);
    }

    public function stockMovementReport(Request $request)
    {
        $query = StockMovement::with(['product', 'warehouse', 'rack', 'user']);

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $movements = $query->latest()->paginate($request->per_page ?? 50);

        return response()->json($movements);
    }

    // Export methods
    public function exportSales(Request $request)
    {
        $query = Sale::with(['warehouse', 'user'])
            ->where('status', 'completed');

        if ($request->start_date) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $sales = $query->latest('sale_date')->get();

        return Excel::download(
            new SalesReportExport($sales),
            'laporan-penjualan-' . date('Y-m-d') . '.xlsx'
        );
    }

    public function exportPurchases(Request $request)
    {
        $query = Purchase::with(['supplier', 'warehouse', 'user'])
            ->where('status', 'received');

        if ($request->start_date) {
            $query->whereDate('purchase_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('purchase_date', '<=', $request->end_date);
        }

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchases = $query->latest('purchase_date')->get();

        return Excel::download(
            new PurchasesReportExport($purchases),
            'laporan-pembelian-' . date('Y-m-d') . '.xlsx'
        );
    }

    public function exportStock(Request $request)
    {
        $query = Product::with(['category', 'baseUnit'])
            ->where('is_active', true);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('low_stock') && $request->low_stock) {
            $query->whereRaw('
                (SELECT COALESCE(SUM(quantity), 0) FROM stocks WHERE product_id = products.id) < products.minimum_stock
            ');
        }

        $query->withSum('stocks as total_stock', 'quantity');

        if ($request->warehouse_id) {
            $query->whereHas('stocks', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        $products = $query->get();

        return Excel::download(
            new StockReportExport($products),
            'laporan-stok-' . date('Y-m-d') . '.xlsx'
        );
    }
}
