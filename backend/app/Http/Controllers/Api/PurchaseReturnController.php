<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Models\ProductUnit;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function index(Request $request)
    {
        $request->user()->can('view_purchases') || abort(403, 'Forbidden');

        $returns = PurchaseReturn::with(['purchase.supplier', 'warehouse', 'user', 'details.product', 'details.unit'])
            ->when($request->search, function ($query, $search) {
                $query->where('return_number', 'like', '%' . $search . '%')
                    ->orWhereHas('purchase', function ($q) use ($search) {
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
        $request->user()->can('create_purchases') || abort(403, 'Forbidden');

        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.purchase_detail_id' => 'required|exists:purchase_details,id',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.unit_id' => 'required|exists:units,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
            'details.*.price' => 'required|numeric|min:0',
            'details.*.subtotal' => 'required|numeric|min:0',
        ]);

        $purchase = Purchase::with('details')->findOrFail($request->purchase_id);
        if ($purchase->status === 'cancelled') {
            return response()->json([
                'message' => 'Pembelian sudah dibatalkan, tidak dapat membuat retur',
            ], 400);
        }

        // Validate each detail: quantity must not exceed remaining returnable
        $purchaseDetailIds = collect($request->details)->pluck('purchase_detail_id')->unique();
        foreach ($purchaseDetailIds as $purchaseDetailId) {
            $purchaseDetail = $purchase->details->firstWhere('id', $purchaseDetailId);
            if (!$purchaseDetail) {
                return response()->json([
                    'message' => 'Detail pembelian tidak valid untuk transaksi ini',
                ], 422);
            }
            $alreadyReturned = PurchaseReturnDetail::where('purchase_detail_id', $purchaseDetailId)->sum('quantity');
            $maxReturn = $purchaseDetail->quantity - $alreadyReturned;
            $requestedQty = collect($request->details)->where('purchase_detail_id', $purchaseDetailId)->sum('quantity');
            if ($requestedQty > $maxReturn) {
                $product = $purchaseDetail->product;
                return response()->json([
                    'message' => "Jumlah retur untuk {$product->name} melebihi sisa yang dapat diretur (maks: {$maxReturn})",
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $lastReturn = PurchaseReturn::whereDate('created_at', today())->latest()->first();
            $number = $lastReturn ? (int) preg_replace('/[^0-9]/', '', substr($lastReturn->return_number, -4)) + 1 : 1;
            $returnNumber = 'PREB-' . date('Ymd') . '-' . str_pad($number, 4, '0', STR_PAD_LEFT); // e.g. PREB-20231010-0001

            $subtotal = collect($request->details)->sum('subtotal');
            $tax = 0; // Or calculate if required
            $discount = 0; // Or calculate if required
            $total = $subtotal - $discount + $tax;

            $purchaseReturn = PurchaseReturn::create([
                'return_number' => $returnNumber,
                'purchase_id' => $purchase->id,
                'warehouse_id' => $purchase->warehouse_id,
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

                PurchaseReturnDetail::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_detail_id' => $detail['purchase_detail_id'],
                    'product_id' => $detail['product_id'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'subtotal' => $detail['subtotal'],
                ]);

                // For Purchase Returns, stock GOES OUT (decreases)
                $stock = Stock::where('product_id', $detail['product_id'])
                    ->where('warehouse_id', $purchase->warehouse_id)
                    ->first();
                if ($stock) {
                    $stock->decrement('quantity', $quantityInBaseUnit);
                } else {
                    // It shouldn't happen usually but just in case
                    Stock::create([
                        'product_id' => $detail['product_id'],
                        'warehouse_id' => $purchase->warehouse_id,
                        'quantity' => -$quantityInBaseUnit,
                    ]);
                }

                StockMovement::create([
                    'product_id' => $detail['product_id'],
                    'warehouse_id' => $purchase->warehouse_id,
                    'type' => 'out',
                    'reference_type' => 'purchase_return',
                    'reference_id' => $purchaseReturn->id,
                    'quantity' => $quantityInBaseUnit,
                    'user_id' => auth()->id(),
                    'notes' => 'Retur pembelian ' . $returnNumber . ' (dari ' . $purchase->invoice_number . ')',
                ]);
            }

            DB::commit();

            $purchaseReturn->load(['purchase', 'warehouse', 'user', 'details.product', 'details.unit']);

            return response()->json([
                'message' => 'Retur pembelian berhasil disimpan',
                'purchase_return' => $purchaseReturn,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan retur pembelian',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(PurchaseReturn $purchase_return)
    {
        request()->user()->can('view_purchases') || abort(403, 'Forbidden');

        $purchase_return->load(['purchase.details.product', 'purchase.details.unit', 'purchase.supplier', 'warehouse', 'user', 'details.product', 'details.unit', 'details.purchaseDetail']);

        return response()->json($purchase_return);
    }
}
