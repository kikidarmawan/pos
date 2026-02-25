<?php

namespace App\Services;

use App\Models\Subscription;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Buat transaksi Snap untuk pembayaran langganan.
     * Menggunakan library midtrans-php: https://github.com/Midtrans/midtrans-php
     * Return ['snap_token' => ..., 'redirect_url' => ...] atau null jika gagal.
     */
    public function createSnapToken(Subscription $subscription): ?array
    {
        $orderId = $subscription->midtrans_order_id;
        $package = $subscription->package;
        $amount = (int) round($package->price);

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'item_details' => [
                [
                    'id' => (string) $package->id,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => $package->name,
                    'category' => 'Subscription',
                ],
            ],
            'customer_details' => [
                'first_name' => 'Pelanggan',
                'email' => $subscription->store->email ?? 'store@example.com',
            ],
        ];

        $finishUrl = config('midtrans.finish_url');
        $errorUrl = config('midtrans.error_url');
        $pendingUrl = config('midtrans.pending_url');
        if ($finishUrl || $errorUrl || $pendingUrl) {
            $params['callbacks'] = [
                'finish' => $finishUrl,
                'error' => $errorUrl,
                'pending' => $pendingUrl,
            ];
        }

        try {
            $snap = Snap::createTransaction($params);

            return [
                'snap_token' => $snap->token ?? null,
                'redirect_url' => $snap->redirect_url ?? null,
            ];
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }
}
