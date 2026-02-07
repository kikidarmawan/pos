<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'PCS', 'name' => 'Pcs', 'description' => 'Pieces - Satuan per buah'],
            ['code' => 'BOX', 'name' => 'Box', 'description' => 'Box - Satuan per kotak'],
            ['code' => 'MTR', 'name' => 'Meter', 'description' => 'Meter - Satuan panjang'],
            ['code' => 'ROLL', 'name' => 'Roll', 'description' => 'Roll - Satuan gulungan'],
            ['code' => 'SET', 'name' => 'Set', 'description' => 'Set - Satuan per set'],
            ['code' => 'KG', 'name' => 'Kilogram', 'description' => 'Kilogram - Satuan berat'],
            ['code' => 'LTR', 'name' => 'Liter', 'description' => 'Liter - Satuan volume'],
            ['code' => 'DZN', 'name' => 'Lusin', 'description' => 'Dozen - Satuan 12 buah'],
            ['code' => 'PACK', 'name' => 'Pack', 'description' => 'Pack - Satuan per paket'],
            ['code' => 'BTL', 'name' => 'Botol', 'description' => 'Bottle - Satuan per botol'],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }

        $this->command->info('Units created successfully!');
    }
}
