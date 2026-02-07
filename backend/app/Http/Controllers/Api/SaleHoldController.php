<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SaleHold;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductUnit;
use Illuminate\Http\Request;

class SaleHoldController extends Controller
{
    public function index(Request $request)
    {
        $holds = SaleHold::with(['warehouse'])
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(20)
            ->get();

        return response()->json($holds);
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
        ]);

        $hold = SaleHold::create([
            'user_id' => auth()->id(),
            'warehouse_id' => $request->warehouse_id,
            'customer_id' => $request->customer_id,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_address' => $request->customer_address,
            'discount' => $request->discount ?? 0,
            'tax' => $request->tax ?? 0,
            'items' => $request->items,
        ]);

        return response()->json([
            'message' => 'Transaksi berhasil ditahan',
            'hold' => $hold,
        ], 201);
    }

    public function show(SaleHold $sale_hold)
    {
        if ($sale_hold->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $items = collect($sale_hold->items)->map(function ($row) {
            $product = Product::with(['baseUnit', 'productUnits.unit'])->find($row['product_id']);
            $unit = Unit::find($row['unit_id']);
            $productUnit = ProductUnit::where('product_id', $row['product_id'])
                ->where('unit_id', $row['unit_id'])->first();

            return [
                'product' => $product,
                'unit' => $unit,
                'productUnit' => $productUnit,
                'quantity' => $row['quantity'],
                'price' => $row['price'],
                'discount' => $row['discount'] ?? 0,
                'subtotal' => $row['subtotal'],
            ];
        })->toArray();

        return response()->json([
            'hold' => $sale_hold,
            'items' => $items,
            'customer' => [
                'id' => $sale_hold->customer_id,
                'name' => $sale_hold->customer_name,
                'phone' => $sale_hold->customer_phone,
                'address' => $sale_hold->customer_address,
            ],
        ]);
    }

    public function destroy(SaleHold $sale_hold)
    {
        if ($sale_hold->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $sale_hold->delete();

        return response()->json([
            'message' => 'Transaksi tertahan berhasil dihapus',
        ]);
    }
}
