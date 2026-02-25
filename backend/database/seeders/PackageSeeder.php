<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Bulanan',
                'code' => 'MONTHLY',
                'description' => 'Langganan 1 bulan',
                'price' => 99000,
                'duration_days' => 30,
                'features' => ['Akses penuh POS', 'Dukungan email'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Tahunan',
                'code' => 'YEARLY',
                'description' => 'Langganan 1 tahun (hemat 2 bulan)',
                'price' => 990000,
                'duration_days' => 365,
                'features' => ['Akses penuh POS', 'Dukungan prioritas', 'Hemat 2 bulan'],
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($packages as $data) {
            Package::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
