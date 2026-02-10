<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $purchases = Purchase::with(['supplier', 'warehouse', 'user'])
            ->when($request->search, function ($query, $search) {
                $query->where('invoice_number', 'like', '%' . $search . '%');
            })
            ->when($request->supplier_id, function ($query, $supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->start_date, function ($query, $startDate) {
                $query->whereDate('purchase_date', '>=', $startDate);
            })
            ->when($request->end_date, function ($query, $endDate) {
                $query->whereDate('purchase_date', '<=', $endDate);
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($purchases);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'purchase_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.unit_id' => 'required|exists:units,id',
            'details.*.rack_id' => 'nullable|exists:racks,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
            'details.*.price' => 'required|numeric|min:0',
            'details.*.subtotal' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Generate invoice number
            $lastPurchase = Purchase::whereDate('created_at', today())->latest()->first();
            $number = $lastPurchase ? intval(substr($lastPurchase->invoice_number, -4)) + 1 : 1;
            $invoiceNumber = 'PO-' . date('Ymd') . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);

            // Create purchase
            $purchase = Purchase::create([
                'invoice_number' => $invoiceNumber,
                'supplier_id' => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'user_id' => auth()->id(),
                'purchase_date' => $request->purchase_date,
                'subtotal' => $request->subtotal,
                'tax' => $request->tax ?? 0,
                'discount' => $request->discount ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'total' => $request->total,
                'notes' => $request->notes,
                'status' => 'received',
            ]);

            // Create purchase details and update stock
            foreach ($request->details as $detail) {
                // Get product unit for conversion (atau base unit jika unit_id = base_unit_id)
                $product = Product::find($detail['product_id']);
                $productUnit = ProductUnit::where('product_id', $detail['product_id'])
                    ->whereId($detail['unit_id'])
                    ->first();

                $conversionFactor = 1;
                if ($productUnit) {
                    $conversionFactor = (float) $productUnit->conversion_factor;
                } elseif ($product && $product->base_unit_id == $detail['unit_id']) {
                    $conversionFactor = 1;
                } else {
                    throw new \Exception('Satuan tidak terkonfigurasi untuk produk ini. Tambahkan satuan di data produk.');
                }

                $quantityInBaseUnit = $detail['quantity'] * $conversionFactor;

                // Create purchase detail
                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $detail['product_id'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'subtotal' => $detail['subtotal'],
                ]);

                // Update or create stock
                $stock = Stock::firstOrCreate(
                    [
                        'product_id' => $detail['product_id'],
                        'warehouse_id' => $request->warehouse_id,
                        'rack_id' => $detail['rack_id'] ?? null,
                    ],
                    ['quantity' => 0]
                );

                $stock->increment('quantity', $quantityInBaseUnit);

                // Create stock movement
                StockMovement::create([
                    'product_id' => $detail['product_id'],
                    'warehouse_id' => $request->warehouse_id,
                    'rack_id' => $detail['rack_id'] ?? null,
                    'type' => 'in',
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'quantity' => $quantityInBaseUnit,
                    'user_id' => auth()->id(),
                    'notes' => 'Pembelian ' . $invoiceNumber,
                ]);
            }

            DB::commit();

            $purchase->load(['supplier', 'warehouse', 'details.product', 'details.unit']);

            return response()->json([
                'message' => 'Pembelian berhasil disimpan',
                'purchase' => $purchase,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan pembelian',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'warehouse', 'user', 'details.product', 'details.unit']);

        return response()->json($purchase);
    }

    public function cancel(Purchase $purchase)
    {
        if ($purchase->status === 'cancelled') {
            return response()->json([
                'message' => 'Pembelian sudah dibatalkan',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Reverse stock
            foreach ($purchase->details as $detail) {
                $product = Product::find($detail->product_id);
                $productUnit = ProductUnit::where('product_id', $detail->product_id)
                    ->where('unit_id', $detail->unit_id)
                    ->first();

                $conversionFactor = $productUnit
                    ? (float) $productUnit->conversion_factor
                    : (($product && $product->base_unit_id == $detail->unit_id) ? 1 : 1);
                $quantityInBaseUnit = $detail->quantity * $conversionFactor;

                $stock = Stock::where('product_id', $detail->product_id)
                    ->where('warehouse_id', $purchase->warehouse_id)
                    ->first();

                if ($stock) {
                    $stock->decrement('quantity', $quantityInBaseUnit);

                    // Create reverse stock movement
                    StockMovement::create([
                        'product_id' => $detail->product_id,
                        'warehouse_id' => $purchase->warehouse_id,
                        'type' => 'out',
                        'reference_type' => 'purchase_cancel',
                        'reference_id' => $purchase->id,
                        'quantity' => $quantityInBaseUnit,
                        'user_id' => auth()->id(),
                        'notes' => 'Batal pembelian ' . $purchase->invoice_number,
                    ]);
                }
            }

            $purchase->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'message' => 'Pembelian berhasil dibatalkan',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membatalkan pembelian',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
