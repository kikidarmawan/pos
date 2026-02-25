<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Store;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Beri toko pertama langganan aktif (untuk development).
     * Di production, langganan hanya dari pembayaran Midtrans.
     */
    public function run(): void
    {
        $store = Store::first();
        $package = Package::where('code', 'YEARLY')->first();
        if (!$store || !$package) {
            return;
        }
        if ($store->hasActiveSubscription()) {
            return;
        }
        Subscription::create([
            'store_id' => $store->id,
            'package_id' => $package->id,
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now(),
            'expires_at' => now()->addDays($package->duration_days),
            'paid_at' => now(),
        ]);
    }
}
