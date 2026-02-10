<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'SAK', 'name' => 'Sak', 'description' => 'Sak - untuk semen, pasir kemasan'],
            ['code' => 'KG', 'name' => 'Kilogram', 'description' => 'Kilogram - satuan berat'],
            ['code' => 'MTR', 'name' => 'Meter', 'description' => 'Meter - satuan panjang'],
            ['code' => 'M2', 'name' => 'Meter Persegi', 'description' => 'M² - satuan luas'],
            ['code' => 'ROL', 'name' => 'Roll', 'description' => 'Roll - gulungan kabel, kawat'],
            ['code' => 'DUS', 'name' => 'Dus', 'description' => 'Dus - kemasan dus'],
            ['code' => 'LBR', 'name' => 'Lembar', 'description' => 'Lembar - triplek, gypsum, asbes'],
            ['code' => 'BTG', 'name' => 'Batang', 'description' => 'Batang - besi, kayu, hollow'],
            ['code' => 'BOX', 'name' => 'Box', 'description' => 'Box - kemasan kotak'],
            ['code' => 'LTR', 'name' => 'Liter', 'description' => 'Liter - cat, thinner, oli'],
            ['code' => 'PCS', 'name' => 'Buah', 'description' => 'Buah/Pcs - satuan per buah'],
            ['code' => 'SET', 'name' => 'Set', 'description' => 'Set - satuan per set'],
            ['code' => 'KRT', 'name' => 'Karton', 'description' => 'Karton - keramik, bata ringan'],
            ['code' => 'PAK', 'name' => 'Pak', 'description' => 'Pak - paket isi beberapa'],
            ['code' => 'BTL', 'name' => 'Botol', 'description' => 'Botol - lem, sealant'],
            ['code' => 'KAL', 'name' => 'Kaleng', 'description' => 'Kaleng - cat kaleng'],
            ['code' => 'M3', 'name' => 'Meter Kubik', 'description' => 'M³ - pasir, batu, kayu'],
            ['code' => 'UNT', 'name' => 'Unit', 'description' => 'Unit - closet, kran, pintu'],
            ['code' => 'BAG', 'name' => 'Bag', 'description' => 'Bag - kemasan karung kecil'],
            ['code' => 'KOD', 'name' => 'Kodi', 'description' => 'Kodi - 20 buah'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['code' => $unit['code']],
                array_merge($unit, ['is_active' => true])
            );
        }

        $this->command->info('Units (toko bahan bangunan) created successfully!');
    }
}
