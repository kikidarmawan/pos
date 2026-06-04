<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\ReceiptPrintService;
use App\Models\SaleDetail;
use App\Models\SaleReturnDetail;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $sales = Sale::with(['warehouse', 'user'])
            ->when($request->search, function ($query, $search) {
                $query->where('invoice_number', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%');
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->start_date, function ($query, $startDate) {
                $query->whereDate('sale_date', '>=', $startDate);
            })
            ->when($request->end_date, function ($query, $endDate) {
                $query->whereDate('sale_date', '<=', $endDate);
            })
            ->when($request->payment_method, function ($query, $paymentMethod) {
                $query->where('payment_method', $paymentMethod);
            })
            ->when($request->payment_status === 'lunas', function ($query) {
                $query->whereRaw('paid >= total');
            })
            ->when($request->payment_status === 'belum_lunas', function ($query) {
                $query->whereRaw('paid < total');
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'sale_date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paid' => 'required|numeric|min:0',
            'change' => 'nullable|numeric',
            'payment_method' => 'required|in:cash,card,transfer,other,credit,cod',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.unit_id' => 'required|exists:units,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
            'details.*.price' => 'required|numeric|min:0',
            'details.*.subtotal' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Validate stock availability
            foreach ($request->details as $detail) {
                $productUnit = ProductUnit::where('product_id', $detail['product_id'])
                    ->where('unit_id', $detail['unit_id'])
                    ->first();

                $quantityInBaseUnit = $detail['quantity'] * $productUnit->conversion_factor;

                $availableStock = Stock::where('product_id', $detail['product_id'])
                    ->where('warehouse_id', $request->warehouse_id)
                    ->sum('quantity');

                if ($availableStock < $quantityInBaseUnit) {
                    $product = Product::find($detail['product_id']);
                    throw new \Exception("Stok {$product->name} tidak mencukupi. Tersedia: {$availableStock}, Dibutuhkan: {$quantityInBaseUnit}");
                }
            }

            // Generate invoice number: pakai sale_date dari request agar tanggal di nomor = tanggal transaksi
            $saleDate = $request->sale_date;
            $datePrefix = \Carbon\Carbon::parse($saleDate)->format('Ymd');
            $lastSale = Sale::where('invoice_number', 'like', 'INV-' . $datePrefix . '-%')->latest('id')->first();
            $number = $lastSale ? intval(substr($lastSale->invoice_number, -4)) + 1 : 1;
            $invoiceNumber = 'INV-' . $datePrefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);

            // Create sale
            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'warehouse_id' => $request->warehouse_id,
                'user_id' => auth()->id(),
                'sale_date' => $request->sale_date,
                'customer_id' => $request->customer_id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'subtotal' => $request->subtotal,
                'tax' => $request->tax ?? 0,
                'discount' => $request->discount ?? 0,
                'total' => $request->total,
                'paid' => $request->paid,
                'change' => max(0, ($request->change ?? ($request->paid - $request->total))),
                'payment_method' => $request->payment_method,
                'status' => 'completed',
            ]);

            // Create sale details and deduct stock (FIFO)
            foreach ($request->details as $detail) {
                // Get product unit for conversion
                $productUnit = ProductUnit::where('product_id', $detail['product_id'])
                    ->where('unit_id', $detail['unit_id'])
                    ->first();

                // Convert to base unit
                $quantityInBaseUnit = $detail['quantity'] * $productUnit->conversion_factor;

                // Create sale detail
                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $detail['product_id'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'subtotal' => $detail['subtotal'],
                ]);

                // Deduct stock using FIFO
                $remainingQty = $quantityInBaseUnit;
                $stocks = Stock::where('product_id', $detail['product_id'])
                    ->where('warehouse_id', $request->warehouse_id)
                    ->where('quantity', '>', 0)
                    ->orderBy('created_at', 'asc') // FIFO
                    ->get();

                foreach ($stocks as $stock) {
                    if ($remainingQty <= 0) break;

                    $deductQty = min($stock->quantity, $remainingQty);
                    $stock->decrement('quantity', $deductQty);
                    $remainingQty -= $deductQty;

                    // Create stock movement
                    StockMovement::create([
                        'product_id' => $detail['product_id'],
                        'warehouse_id' => $request->warehouse_id,
                        'rack_id' => $stock->rack_id,
                        'type' => 'out',
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'quantity' => $deductQty,
                        'user_id' => auth()->id(),
                        'notes' => 'Penjualan ' . $invoiceNumber,
                    ]);
                }
            }

            DB::commit();

            $sale->load(['warehouse', 'user', 'details.product', 'details.unit']);

            return response()->json([
                'message' => 'Penjualan berhasil disimpan',
                'sale' => $sale,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan penjualan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Sale $sale)
    {
        $sale->load([
            'warehouse',
            'user',
            'details.product',
            'details.unit',
            'saleReturns' => fn ($q) => $q->where('status', 'completed')->with(['details.product', 'details.unit', 'user']),
        ]);

        foreach ($sale->details as $detail) {
            $returned = SaleReturnDetail::where('sale_detail_id', $detail->id)->sum('quantity');
            $detail->returned_quantity = (float) $returned;
            $detail->returnable_quantity = (float) $detail->quantity - $detail->returned_quantity;
        }

        return response()->json($sale);
    }

    public function addPayment(Request $request, Sale $sale)
    {
        if ($sale->status === 'cancelled') {
            return response()->json([
                'message' => 'Transaksi sudah dibatalkan',
            ], 400);
        }

        $total = (float) $sale->total;
        $paid = (float) $sale->paid;
        $remaining = $total - $paid;

        if ($remaining <= 0) {
            return response()->json([
                'message' => 'Transaksi sudah lunas',
            ], 400);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $amount = (float) $request->amount;

        if ($amount > $remaining) {
            return response()->json([
                'message' => 'Jumlah pembayaran melebihi sisa utang (Rp ' . number_format($remaining, 0, ',', '.') . ')',
            ], 422);
        }

        $newPaid = $paid + $amount;
        $newChange = max(0, $newPaid - $total);

        $sale->update([
            'paid' => $newPaid,
            'change' => $newChange,
        ]);

        $sale->load(['warehouse', 'user', 'details.product', 'details.unit']);

        return response()->json([
            'message' => 'Pembayaran berhasil dicatat',
            'sale' => $sale,
        ]);
    }

    public function cancel(Sale $sale)
    {
        if ($sale->status === 'cancelled') {
            return response()->json([
                'message' => 'Penjualan sudah dibatalkan',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Return stock
            foreach ($sale->details as $detail) {
                $productUnit = ProductUnit::where('product_id', $detail->product_id)
                    ->where('unit_id', $detail->unit_id)
                    ->first();

                $quantityInBaseUnit = $detail->quantity * $productUnit->conversion_factor;

                // Add back to stock (first available location)
                $stock = Stock::where('product_id', $detail->product_id)
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->first();

                if ($stock) {
                    $stock->increment('quantity', $quantityInBaseUnit);
                } else {
                    Stock::create([
                        'product_id' => $detail->product_id,
                        'warehouse_id' => $sale->warehouse_id,
                        'quantity' => $quantityInBaseUnit,
                    ]);
                }

                // Create reverse stock movement
                StockMovement::create([
                    'product_id' => $detail->product_id,
                    'warehouse_id' => $sale->warehouse_id,
                    'type' => 'in',
                    'reference_type' => 'sale_cancel',
                    'reference_id' => $sale->id,
                    'quantity' => $quantityInBaseUnit,
                    'user_id' => auth()->id(),
                    'notes' => 'Batal penjualan ' . $sale->invoice_number,
                ]);
            }

            $sale->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'message' => 'Penjualan berhasil dibatalkan',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membatalkan penjualan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cetak struk ke printer thermal via backend (ESC/POS)
     * Printer harus terhubung ke server dan diatur di .env RECEIPT_PRINTER
     */
    public function printReceipt(Sale $sale)
    {
        if ($sale->status === 'cancelled') {
            return response()->json(['message' => 'Transaksi sudah dibatalkan'], 400);
        }

        try {
            $service = new ReceiptPrintService();
            $service->printReceipt($sale);

            return response()->json([
                'message' => 'Struk berhasil dicetak ke printer',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal cetak struk',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
