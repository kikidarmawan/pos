<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $stocks = Stock::with([
            'product' => fn ($q) => $q->withSum('stocks as total_stock', 'quantity'),
            'product.category',
            'product.baseUnit',
            'product.productUnits.unit',
            'warehouse',
            'rack',
        ])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                });
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->rack_id, function ($query, $rackId) {
                $query->where('rack_id', $rackId);
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->whereHas('product', function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            })
            ->when($request->has('low_stock') && $request->low_stock, function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->whereRaw('stocks.quantity < products.minimum_stock');
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($stocks);
    }

    public function adjustment(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'rack_id' => 'nullable|exists:racks,id',
            'quantity' => 'required|numeric|min:0',
            'unit_id' => 'nullable|exists:units,id',
            'type' => 'required|in:in,out,adjustment',
            'notes' => 'nullable|string',
        ]);

        $quantityInBaseUnit = $request->quantity;

        // Konversi ke base unit jika unit_id diberikan (misal: 2 Roll = 200 Meter)
        if ($request->unit_id) {
            $productUnit = ProductUnit::where('product_id', $request->product_id)
                ->where('unit_id', $request->unit_id)
                ->first();

            if ($productUnit) {
                $quantityInBaseUnit = $productUnit->toBaseUnit($request->quantity);
            }
        }

        DB::beginTransaction();
        try {
            $stock = Stock::firstOrCreate(
                [
                    'product_id' => $request->product_id,
                    'warehouse_id' => $request->warehouse_id,
                    'rack_id' => $request->rack_id,
                ],
                ['quantity' => 0]
            );

            if ($request->type === 'in' || $request->type === 'adjustment') {
                $stock->increment('quantity', $quantityInBaseUnit);
            } else {
                $stock->decrement('quantity', $quantityInBaseUnit);
            }

            StockMovement::create([
                'product_id' => $request->product_id,
                'warehouse_id' => $request->warehouse_id,
                'rack_id' => $request->rack_id,
                'type' => $request->type,
                'reference_type' => 'adjustment',
                'quantity' => $quantityInBaseUnit,
                'user_id' => auth()->id(),
                'notes' => $request->notes ?? 'Penyesuaian stok manual',
            ]);

            DB::commit();

            $stock->load(['product', 'warehouse', 'rack']);

            return response()->json([
                'message' => 'Stok berhasil disesuaikan',
                'stock' => $stock,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyesuaikan stok',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function movements(Request $request)
    {
        $query = StockMovement::with(['product', 'warehouse', 'rack', 'user']);

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $movements = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json($movements);
    }
}
