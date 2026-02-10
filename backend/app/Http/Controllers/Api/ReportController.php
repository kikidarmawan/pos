<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Store;
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
        $today = today()->format('Y-m-d');
        $yearMonth = [date('Y'), date('m')];

        // Today's sales (bruto) minus retur hari ini
        $todaySalesBruto = Sale::whereDate('sale_date', $today)
            ->where('status', 'completed')
            ->sum('total');
        $todayReturns = SaleReturn::whereDate('return_date', $today)
            ->where('status', 'completed')
            ->sum('total');
        $todaySales = $todaySalesBruto - $todayReturns;

        $todayTransactions = Sale::whereDate('sale_date', $today)
            ->where('status', 'completed')
            ->count();

        // This month's sales (bruto) minus retur bulan ini
        $monthSalesBruto = Sale::whereYear('sale_date', $yearMonth[0])
            ->whereMonth('sale_date', $yearMonth[1])
            ->where('status', 'completed')
            ->sum('total');
        $monthReturns = SaleReturn::whereYear('return_date', $yearMonth[0])
            ->whereMonth('return_date', $yearMonth[1])
            ->where('status', 'completed')
            ->sum('total');
        $monthSales = $monthSalesBruto - $monthReturns;

        // Low stock products
        $lowStockProducts = Product::whereRaw('
            (SELECT COALESCE(SUM(quantity), 0) FROM stocks WHERE product_id = products.id) < products.minimum_stock
        ')->count();

        // Total products
        $totalProducts = Product::where('is_active', true)->count();

        // Top selling products this month (net: penjualan - retur)
        $salesByProduct = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereYear('sales.sale_date', $yearMonth[0])
            ->whereMonth('sales.sale_date', $yearMonth[1])
            ->where('sales.status', 'completed')
            ->select(
                'products.id',
                'products.name',
                DB::raw('COALESCE(SUM(sale_details.quantity), 0) as qty_sold'),
                DB::raw('COALESCE(SUM(sale_details.subtotal), 0) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->get()
            ->keyBy('id');

        $returnsByProduct = DB::table('sale_return_details')
            ->join('sale_returns', 'sale_return_details.sale_return_id', '=', 'sale_returns.id')
            ->join('products', 'sale_return_details.product_id', '=', 'products.id')
            ->whereYear('sale_returns.return_date', $yearMonth[0])
            ->whereMonth('sale_returns.return_date', $yearMonth[1])
            ->where('sale_returns.status', 'completed')
            ->select(
                'products.id',
                DB::raw('COALESCE(SUM(sale_return_details.quantity), 0) as qty_returned'),
                DB::raw('COALESCE(SUM(sale_return_details.subtotal), 0) as return_revenue')
            )
            ->groupBy('products.id')
            ->get()
            ->keyBy('id');

        $topProducts = $salesByProduct->map(function ($row) use ($returnsByProduct) {
            $ret = $returnsByProduct->get($row->id);
            $qtyReturned = $ret ? (float) $ret->qty_returned : 0;
            $returnRev = $ret ? (float) $ret->return_revenue : 0;
            $total_sold = max(0, (float) $row->qty_sold - $qtyReturned);
            $total_revenue = max(0, (float) $row->revenue - $returnRev);
            return (object) [
                'id' => $row->id,
                'name' => $row->name,
                'total_sold' => $total_sold,
                'total_revenue' => $total_revenue,
            ];
        })->filter(fn ($p) => $p->total_revenue > 0)->sortByDesc('total_revenue')->take(5)->values();

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

        // Total retur dalam periode (sesuai filter tanggal)
        $returnsQuery = SaleReturn::where('status', 'completed');
        if ($request->start_date) {
            $returnsQuery->whereDate('return_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $returnsQuery->whereDate('return_date', '<=', $request->end_date);
        }
        if ($request->warehouse_id) {
            $returnsQuery->where('warehouse_id', $request->warehouse_id);
        }
        $totalReturns = $returnsQuery->sum('total');

        $totalRevenue = $sales->sum('total');
        $summary = [
            'total_sales' => $sales->count(),
            'total_revenue' => $totalRevenue,
            'total_returns' => $totalReturns,
            'net_revenue' => $totalRevenue - $totalReturns,
            'total_discount' => $sales->sum('discount'),
            'total_tax' => $sales->sum('tax'),
        ];

        return response()->json([
            'sales' => $sales,
            'summary' => $summary,
        ]);
    }

    /**
     * Laporan Pembayaran Penjualan - fokus pada status pembayaran
     */
    public function sellPaymentReport(Request $request)
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

        if ($request->payment_status === 'lunas') {
            $query->whereColumn('paid', '>=', 'total');
        } elseif ($request->payment_status === 'belum_lunas') {
            $query->whereColumn('paid', '<', 'total');
        }

        $sales = $query->latest('sale_date')->get();

        // Total retur dalam periode
        $returnsQuery = SaleReturn::where('status', 'completed');
        if ($request->start_date) {
            $returnsQuery->whereDate('return_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $returnsQuery->whereDate('return_date', '<=', $request->end_date);
        }
        if ($request->warehouse_id) {
            $returnsQuery->where('warehouse_id', $request->warehouse_id);
        }
        $totalReturns = $returnsQuery->sum('total');

        $totalRevenue = $sales->sum('total');
        $totalPaid = $sales->sum('paid');
        $totalDebt = $sales->sum(function ($s) {
            return max(0, (float) $s->total - (float) $s->paid);
        });

        $summary = [
            'total_sales' => $sales->count(),
            'total_revenue' => $totalRevenue,
            'total_returns' => $totalReturns,
            'net_revenue' => $totalRevenue - $totalReturns,
            'total_paid' => $totalPaid,
            'total_debt' => $totalDebt,
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

        if ($request->boolean('low_stock')) {
            $query->whereRaw('
                (SELECT COALESCE(SUM(quantity), 0) FROM stocks WHERE product_id = products.id' .
                ($request->warehouse_id ? ' AND warehouse_id = ' . (int) $request->warehouse_id : '') . '
                ) < products.minimum_stock
            ');
        }

        // Add stock info - filter by warehouse jika ada
        $warehouseFilter = $request->warehouse_id
            ? ' AND warehouse_id = ' . (int) $request->warehouse_id
            : '';
        $query->selectRaw('products.*, (SELECT COALESCE(SUM(quantity), 0) FROM stocks WHERE product_id = products.id' . $warehouseFilter . ') as total_stock');

        $products = $query->orderBy('name')->get();

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

        // Penjualan per produk (periode)
        $salesByProduct = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('COALESCE(SUM(sale_details.quantity), 0) as qty_sold'),
                DB::raw('COALESCE(SUM(sale_details.subtotal), 0) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->get()
            ->keyBy('product_id');

        // Retur per produk (periode)
        $returnsByProduct = DB::table('sale_return_details')
            ->join('sale_returns', 'sale_return_details.sale_return_id', '=', 'sale_returns.id')
            ->join('products', 'sale_return_details.product_id', '=', 'products.id')
            ->whereBetween('sale_returns.return_date', [$startDate, $endDate])
            ->where('sale_returns.status', 'completed')
            ->select(
                'products.id as product_id',
                DB::raw('COALESCE(SUM(sale_return_details.quantity), 0) as qty_returned'),
                DB::raw('COALESCE(SUM(sale_return_details.subtotal), 0) as return_revenue')
            )
            ->groupBy('products.id')
            ->get()
            ->keyBy('product_id');

        $productIds = $salesByProduct->keys()->merge($returnsByProduct->keys())->unique();
        $productProfit = collect($productIds)->map(function ($productId) use ($salesByProduct, $returnsByProduct) {
            $sale = $salesByProduct->get($productId);
            $ret = $returnsByProduct->get($productId);
            $qtySold = $sale ? (float) $sale->qty_sold : 0;
            $revenue = $sale ? (float) $sale->revenue : 0;
            $qtyReturned = $ret ? (float) $ret->qty_returned : 0;
            $returnRev = $ret ? (float) $ret->return_revenue : 0;
            $quantity_sold = max(0, $qtySold - $qtyReturned);
            $netRevenue = max(0, $revenue - $returnRev);
            $product = Product::find($productId);
            $cost = $product ? $quantity_sold * $product->base_price * 0.7 : 0;
            $profit = $netRevenue - $cost;
            $name = ($sale && isset($sale->product_name)) ? $sale->product_name : ($product?->name ?? 'Produk #' . $productId);
            return [
                'product' => [
                    'id' => $productId,
                    'name' => $name,
                ],
                'quantity_sold' => $quantity_sold,
                'revenue' => $netRevenue,
                'cost' => $cost,
                'profit' => $profit,
            ];
        })->filter(fn ($p) => $p['quantity_sold'] > 0 || $p['revenue'] > 0)->values();

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

    /**
     * Laporan Supplier & Pelanggan - total jumlah belanja per customer/supplier
     * type: customer | supplier
     */
    public function supplierCustomerReport(Request $request)
    {
        $type = $request->type ?? 'customer';

        if ($type === 'customer') {
            // Total belanja = sum(sales.total) - sum(sale_returns.total) untuk penjualan pelanggan ini
            $salesSub = 'SELECT COALESCE(SUM(sales.total), 0) FROM sales WHERE sales.customer_id = customers.id AND sales.status = "completed"';
            if ($request->start_date) {
                $salesSub .= ' AND sales.sale_date >= "' . $request->start_date . '"';
            }
            if ($request->end_date) {
                $salesSub .= ' AND sales.sale_date <= "' . $request->end_date . '"';
            }
            $returnsSub = 'SELECT COALESCE(SUM(sale_returns.total), 0) FROM sale_returns INNER JOIN sales ON sale_returns.sale_id = sales.id WHERE sales.customer_id = customers.id AND sale_returns.status = "completed"';
            if ($request->start_date) {
                $returnsSub .= ' AND sale_returns.return_date >= "' . $request->start_date . '"';
            }
            if ($request->end_date) {
                $returnsSub .= ' AND sale_returns.return_date <= "' . $request->end_date . '"';
            }
            $data = Customer::selectRaw('customers.*, ((' . $salesSub . ') - (' . $returnsSub . ')) as total_belanja')
                ->orderByRaw('total_belanja DESC')
                ->get();
        } else {
            $subQuery = 'SELECT COALESCE(SUM(purchases.total), 0) FROM purchases WHERE purchases.supplier_id = suppliers.id AND purchases.status = "received"';
            if ($request->start_date) {
                $subQuery .= ' AND purchases.purchase_date >= "' . $request->start_date . '"';
            }
            if ($request->end_date) {
                $subQuery .= ' AND purchases.purchase_date <= "' . $request->end_date . '"';
            }

            $data = Supplier::selectRaw('suppliers.*, (' . $subQuery . ') as total_belanja')
                ->orderByRaw('total_belanja DESC')
                ->get();
        }

        $summary = [
            'total_rows' => $data->count(),
            'total_belanja' => $data->sum('total_belanja'),
        ];

        return response()->json([
            'data' => $data,
            'summary' => $summary,
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

    /**
     * Daily Cashier POS Summary - laporan kasir dengan date range
     * GET ?start_date=Y-m-d&end_date=Y-m-d&warehouse_id=&user_id=
     */
    public function dailyCashierSummary(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $store = Store::current();

        $query = Sale::with(['warehouse', 'user'])
            ->where('status', 'completed')
            ->whereDate('sale_date', '>=', $startDate)
            ->whereDate('sale_date', '<=', $endDate);

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        $sales = $query->orderBy('sale_date')->orderBy('created_at')->get();

        $returnsQuery = SaleReturn::with(['sale', 'user'])
            ->where('status', 'completed')
            ->whereDate('return_date', '>=', $startDate)
            ->whereDate('return_date', '<=', $endDate);
        if ($request->warehouse_id) {
            $returnsQuery->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->user_id) {
            $returnsQuery->where('user_id', $request->user_id);
        }
        $saleReturns = $returnsQuery->orderBy('created_at')->get();
        $totalReturns = $saleReturns->sum('total');

        $totalRevenue = $sales->sum('total');
        $byPaymentMethod = [
            'cash' => 0,
            'card' => 0,
            'transfer' => 0,
            'other' => 0,
            'credit' => 0,
        ];
        foreach ($sales as $sale) {
            $method = $sale->payment_method ?? 'cash';
            if (isset($byPaymentMethod[$method])) {
                $byPaymentMethod[$method] += (float) $sale->total;
            } else {
                $byPaymentMethod['other'] += (float) $sale->total;
            }
        }

        $summary = [
            'total_transactions' => $sales->count(),
            'total_revenue' => $totalRevenue,
            'total_returns' => $totalReturns,
            'net_revenue' => $totalRevenue - $totalReturns,
            'by_payment_method' => $byPaymentMethod,
        ];

        return response()->json([
            'store' => $store ? [
                'name' => $store->name,
                'address' => $store->address,
                'phone' => $store->phone,
            ] : ['name' => 'Toko', 'address' => null, 'phone' => null],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'sales' => $sales,
            'sale_returns' => $saleReturns,
            'summary' => $summary,
        ]);
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

        if ($request->boolean('low_stock')) {
            $warehouseFilter = $request->warehouse_id ? ' AND warehouse_id = ' . (int) $request->warehouse_id : '';
            $query->whereRaw('
                (SELECT COALESCE(SUM(quantity), 0) FROM stocks WHERE product_id = products.id' . $warehouseFilter . ') < products.minimum_stock
            ');
        }

        $warehouseFilter = $request->warehouse_id ? ' AND warehouse_id = ' . (int) $request->warehouse_id : '';
        $query->selectRaw('products.*, (SELECT COALESCE(SUM(quantity), 0) FROM stocks WHERE product_id = products.id' . $warehouseFilter . ') as total_stock');

        $products = $query->orderBy('name')->get();

        return Excel::download(
            new StockReportExport($products),
            'laporan-stok-' . date('Y-m-d') . '.xlsx'
        );
    }
}
