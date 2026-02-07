<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        if (Store::count() > 0) {
            return;
        }
        Store::create([
            'name' => 'Toko Saya',
            'address' => null,
            'phone' => null,
            'email' => null,
        ]);
    }
}
