<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\SaleDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $deliveries = Delivery::with(['driver', 'vehicle', 'user'])
            ->when($request->search, function ($query, $search) {
                $query->where('delivery_number', 'like', "%{$search}%")
                    ->orWhereHas('driver', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle', function ($q) use ($search) {
                        $q->where('license_plate', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($request->per_page ?? 25);

        return response()->json($deliveries);
    }

    public function getPendingItems(Request $request)
    {
        $pendingItems = SaleDetail::with(['sale' => function($q) {
                $q->select('id', 'invoice_number', 'customer_name', 'customer_phone', 'customer_address', 'sale_date', 'status');
            }, 'product:id,name,code', 'unit:id,name'])
            ->withSum(['deliveryItems as shipped_qty' => function ($q) {
                $q->whereHas('delivery', function ($query) {
                    $query->where('status', '!=', 'canceled');
                });
            }], 'quantity')
            ->whereHas('sale', function ($query) {
                $query->where('status', '!=', 'canceled');
            })
            ->get()
            ->map(function ($detail) {
                $shipped = (float) ($detail->shipped_qty ?? 0);
                $detail->shipped_qty = $shipped;
                $detail->remaining_qty = (float) $detail->quantity - $shipped;
                return $detail;
            })
            ->filter(function ($detail) {
                return $detail->remaining_qty > 0;
            })
            ->values();

        return response()->json($pendingItems);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'driver_id' => 'required|exists:drivers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sale_detail_id' => 'required|exists:sale_details,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            // Validate all items belong to the requested sale_id
            foreach ($request->items as $item) {
                $saleDetail = SaleDetail::findOrFail($item['sale_detail_id']);
                if ($saleDetail->sale_id !== (int) $request->sale_id) {
                    throw new \Exception("Item pengiriman harus berasal dari nota transaksi yang sama.");
                }
            }

            $prefix = 'P' . date('ym');
            $last = Delivery::where('delivery_number', 'like', $prefix . '%')
                ->orderBy('delivery_number', 'desc')
                ->first();
            
            $nextNum = 1;
            if ($last) {
                $lastNum = (int) substr($last->delivery_number, -4);
                $nextNum = $lastNum + 1;
            }
            $deliveryNumber = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $delivery = Delivery::create([
                'user_id' => auth()->id(),
                'sale_id' => $request->sale_id,
                'delivery_number' => $deliveryNumber,
                'driver_id' => $request->driver_id,
                'vehicle_id' => $request->vehicle_id,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                // Verify quantity doesn't exceed remaining
                $shipped = DeliveryItem::where('sale_detail_id', $item['sale_detail_id'])
                    ->whereHas('delivery', function ($q) {
                        $q->where('status', '!=', 'canceled');
                    })->sum('quantity');
                    
                $saleDetail = SaleDetail::findOrFail($item['sale_detail_id']);
                $remaining = $saleDetail->quantity - $shipped;
                
                if ($item['quantity'] > $remaining) {
                    throw new \Exception("Kuantitas pengiriman melebihi sisa untuk produk tertentu.");
                }

                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'sale_detail_id' => $item['sale_detail_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'Pengiriman berhasil dibuat',
                'delivery' => $delivery->load('items'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $delivery = Delivery::with(['driver', 'vehicle', 'items.saleDetail.sale', 'items.saleDetail.product', 'items.saleDetail.unit'])->findOrFail($id);
        return response()->json($delivery);
    }

    public function depart(Delivery $delivery)
    {
        if ($delivery->status !== 'pending') {
            return response()->json(['message' => 'Hanya pengiriman pending yang bisa diberangkatkan'], 400);
        }

        $delivery->update([
            'status' => 'departing',
            'departed_at' => now(),
        ]);

        return response()->json(['message' => 'Pengiriman diberangkatkan', 'delivery' => $delivery]);
    }

    public function complete(Delivery $delivery)
    {
        if ($delivery->status !== 'departing') {
            return response()->json(['message' => 'Pengiriman harus berangkat terlebih dahulu'], 400);
        }

        $delivery->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json(['message' => 'Pengiriman selesai', 'delivery' => $delivery]);
    }

    public function cancel(Delivery $delivery)
    {
        if ($delivery->status === 'completed') {
            return response()->json(['message' => 'Pengiriman selesai tidak bisa dibatalkan'], 400);
        }

        $delivery->update([
            'status' => 'canceled',
        ]);

        return response()->json(['message' => 'Pengiriman dibatalkan', 'delivery' => $delivery]);
    }
}
