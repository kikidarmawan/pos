<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetail;
use App\Models\ProductUnit;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $request->user()->can('view_sales') || abort(403, 'Forbidden');

        $returns = SaleReturn::with(['sale', 'warehouse', 'user', 'details.product', 'details.unit'])
            ->when($request->search, function ($query, $search) {
                $query->where('return_number', 'like', '%' . $search . '%')
                    ->orWhereHas('sale', function ($q) use ($search) {
                        $q->where('invoice_number', 'like', '%' . $search . '%');
                    });
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->start_date, function ($query, $startDate) {
                $query->whereDate('return_date', '>=', $startDate);
            })
            ->when($request->end_date, function ($query, $endDate) {
                $query->whereDate('return_date', '<=', $endDate);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($returns);
    }

    public function store(Request $request)
    {
        $request->user()->can('create_sales') || abort(403, 'Forbidden');

        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.sale_detail_id' => 'required|exists:sale_details,id',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.unit_id' => 'required|exists:units,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
            'details.*.price' => 'required|numeric|min:0',
            'details.*.subtotal' => 'required|numeric|min:0',
        ]);

        $sale = Sale::with('details')->findOrFail($request->sale_id);
        if ($sale->status === 'cancelled') {
            return response()->json([
                'message' => 'Penjualan sudah dibatalkan, tidak dapat membuat retur',
            ], 400);
        }

        // Validate each detail: quantity must not exceed remaining returnable
        $saleDetailIds = collect($request->details)->pluck('sale_detail_id')->unique();
        foreach ($saleDetailIds as $saleDetailId) {
            $saleDetail = $sale->details->firstWhere('id', $saleDetailId);
            if (!$saleDetail) {
                return response()->json([
                    'message' => 'Detail penjualan tidak valid untuk transaksi ini',
                ], 422);
            }
            $alreadyReturned = SaleReturnDetail::where('sale_detail_id', $saleDetailId)->sum('quantity');
            $maxReturn = $saleDetail->quantity - $alreadyReturned;
            $requestedQty = collect($request->details)->where('sale_detail_id', $saleDetailId)->sum('quantity');
            if ($requestedQty > $maxReturn) {
                $product = $saleDetail->product;
                return response()->json([
                    'message' => "Jumlah retur untuk {$product->name} melebihi sisa yang dapat diretur (maks: {$maxReturn})",
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $lastReturn = SaleReturn::whereDate('created_at', today())->latest()->first();
            $number = $lastReturn ? (int) preg_replace('/[^0-9]/', '', substr($lastReturn->return_number, -4)) + 1 : 1;
            $returnNumber = 'RET-' . date('Ymd') . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);

            $subtotal = collect($request->details)->sum('subtotal');
            $tax = 0;
            $discount = 0;
            $total = $subtotal - $discount + $tax;

            $saleReturn = SaleReturn::create([
                'return_number' => $returnNumber,
                'sale_id' => $sale->id,
                'warehouse_id' => $sale->warehouse_id,
                'user_id' => auth()->id(),
                'return_date' => $request->return_date,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'notes' => $request->notes,
                'status' => 'completed',
            ]);

            foreach ($request->details as $detail) {
                $productUnit = ProductUnit::where('product_id', $detail['product_id'])
                    ->where('unit_id', $detail['unit_id'])
                    ->firstOrFail();
                $quantityInBaseUnit = $detail['quantity'] * $productUnit->conversion_factor;

                SaleReturnDetail::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_detail_id' => $detail['sale_detail_id'],
                    'product_id' => $detail['product_id'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'subtotal' => $detail['subtotal'],
                ]);

                $stock = Stock::where('product_id', $detail['product_id'])
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->first();
                if ($stock) {
                    $stock->increment('quantity', $quantityInBaseUnit);
                } else {
                    Stock::create([
                        'product_id' => $detail['product_id'],
                        'warehouse_id' => $sale->warehouse_id,
                        'quantity' => $quantityInBaseUnit,
                    ]);
                }

                StockMovement::create([
                    'product_id' => $detail['product_id'],
                    'warehouse_id' => $sale->warehouse_id,
                    'type' => 'in',
                    'reference_type' => 'sale_return',
                    'reference_id' => $saleReturn->id,
                    'quantity' => $quantityInBaseUnit,
                    'user_id' => auth()->id(),
                    'notes' => 'Retur penjualan ' . $returnNumber . ' (dari ' . $sale->invoice_number . ')',
                ]);
            }

            DB::commit();

            $saleReturn->load(['sale', 'warehouse', 'user', 'details.product', 'details.unit']);

            return response()->json([
                'message' => 'Retur penjualan berhasil disimpan',
                'sale_return' => $saleReturn,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan retur penjualan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(SaleReturn $sale_return)
    {
        request()->user()->can('view_sales') || abort(403, 'Forbidden');

        $sale_return->load(['sale.details.product', 'sale.details.unit', 'warehouse', 'user', 'details.product', 'details.unit', 'details.saleDetail']);

        return response()->json($sale_return);
    }
}
