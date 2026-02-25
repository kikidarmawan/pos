<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Store;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Status langganan toko saat ini
     */
    public function current()
    {
        $store = Store::current();
        if (!$store) {
            return response()->json([
                'has_active' => false,
                'subscription' => null,
            ]);
        }
        $active = $store->activeSubscription();
        return response()->json([
            'has_active' => $active !== null,
            'subscription' => $active ? [
                'id' => $active->id,
                'status' => $active->status,
                'started_at' => $active->started_at?->toIso8601String(),
                'expires_at' => $active->expires_at?->toIso8601String(),
                'package' => $active->package ? [
                    'id' => $active->package->id,
                    'name' => $active->package->name,
                    'code' => $active->package->code,
                    'duration_days' => $active->package->duration_days,
                ] : null,
            ] : null,
        ]);
    }

    /**
     * Riwayat langganan toko
     */
    public function index()
    {
        $store = Store::current();
        if (!$store) {
            return response()->json([]);
        }
        $list = $store->subscriptions()
            ->with('package')
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (Subscription $s) {
                return [
                    'id' => $s->id,
                    'status' => $s->status,
                    'started_at' => $s->started_at?->toIso8601String(),
                    'expires_at' => $s->expires_at?->toIso8601String(),
                    'paid_at' => $s->paid_at?->toIso8601String(),
                    'package' => $s->package ? [
                        'id' => $s->package->id,
                        'name' => $s->package->name,
                        'duration_days' => $s->package->duration_days,
                    ] : null,
                ];
            });
        return response()->json($list);
    }

    /**
     * Buat langganan baru (pending) untuk pembayaran manual transfer.
     * Mengembalikan data rekening dan jumlah yang harus ditransfer.
     */
    public function store(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $store = Store::current();
        if (!$store) {
            return response()->json(['message' => 'Toko tidak ditemukan.'], 404);
        }

        $package = Package::findOrFail($request->package_id);
        if (!$package->is_active) {
            return response()->json(['message' => 'Paket tidak tersedia.'], 422);
        }

        $orderId = 'SUB-' . $store->id . '-' . uniqid() . '-' . time();
        $subscription = Subscription::create([
            'store_id' => $store->id,
            'package_id' => $package->id,
            'status' => Subscription::STATUS_PENDING,
            'midtrans_order_id' => $orderId,
        ]);

        $amount = (int) round($package->price);
        $bankTransfer = [
            'bca' => ['bank' => 'BCA', 'account_number' => '4310366141', 'account_name' => 'Kiki Darmawan'],
            'sea_bank' => ['bank' => 'Sea Bank', 'account_number' => '901250504500', 'account_name' => 'Kiki Darmawan'],
        ];
        $confirm_phone = '0815 7111 413';

        return response()->json([
            'message' => 'Silakan transfer ke rekening berikut.',
            'subscription_id' => $subscription->id,
            'order_id' => $orderId,
            'amount' => $amount,
            'package_name' => $package->name,
            'bank_transfer' => $bankTransfer,
            'confirm_phone' => $confirm_phone,
            'confirm_message' => 'Jika sudah membayar, harap hubungi ' . $confirm_phone . ' untuk konfirmasi pembayaran.',
        ], 201);
    }

    /**
     * Webhook Midtrans notification (POST dari Midtrans).
     * Payload mengikuti format Midtrans; logic mengacu pada docs midtrans-php.
     */
    public function midtransNotification(Request $request)
    {
        $payload = $request->all();
        if (is_string($request->getContent()) && $request->getContent() !== '') {
            $decoded = json_decode($request->getContent(), true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? 'accept';

        if (!$orderId) {
            return response()->json(['message' => 'Invalid'], 400);
        }

        $subscription = Subscription::where('midtrans_order_id', $orderId)->first();
        if (!$subscription) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'challenge') {
                // Bisa set status khusus 'challenge' jika perlu
            } elseif ($fraudStatus === 'accept') {
                $this->activateSubscription($subscription, $payload);
            }
        } elseif ($transactionStatus === 'settlement') {
            $this->activateSubscription($subscription, $payload);
        } elseif ($transactionStatus === 'pending') {
            // Tetap pending
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $subscription->update([
                'status' => Subscription::STATUS_CANCELLED,
                'midtrans_transaction_status' => $transactionStatus,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    private function activateSubscription(Subscription $subscription, array $payload): void
    {
        $subscription->update([
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now(),
            'expires_at' => now()->addDays($subscription->package->duration_days),
            'midtrans_payment_type' => $payload['payment_type'] ?? null,
            'midtrans_transaction_status' => $payload['transaction_status'] ?? null,
            'paid_at' => now(),
        ]);
    }
}
