<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\Rack;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        // Gudang Utama
        $gudangUtama = Warehouse::create([
            'code' => 'GDG-01',
            'name' => 'Gudang Utama',
            'address' => 'Jl. Raya Industri No. 123',
            'phone' => '021-12345678',
            'is_active' => true,
        ]);

        // Rak untuk Gudang Utama
        $racks = [
            ['code' => 'A1', 'name' => 'Rak A1', 'description' => 'Rak bagian depan kiri'],
            ['code' => 'A2', 'name' => 'Rak A2', 'description' => 'Rak bagian depan kanan'],
            ['code' => 'B1', 'name' => 'Rak B1', 'description' => 'Rak bagian tengah kiri'],
            ['code' => 'B2', 'name' => 'Rak B2', 'description' => 'Rak bagian tengah kanan'],
            ['code' => 'C1', 'name' => 'Rak C1', 'description' => 'Rak bagian belakang kiri'],
            ['code' => 'C2', 'name' => 'Rak C2', 'description' => 'Rak bagian belakang kanan'],
        ];

        foreach ($racks as $rack) {
            Rack::create(array_merge($rack, ['warehouse_id' => $gudangUtama->id]));
        }

        // Toko/Showroom
        $toko = Warehouse::create([
            'code' => 'TKO-01',
            'name' => 'Toko/Showroom',
            'address' => 'Jl. Raya Utama No. 456',
            'phone' => '021-87654321',
            'is_active' => true,
        ]);

        // Rak untuk Toko
        $tokoRacks = [
            ['code' => 'D1', 'name' => 'Display 1', 'description' => 'Display depan'],
            ['code' => 'D2', 'name' => 'Display 2', 'description' => 'Display samping'],
            ['code' => 'S1', 'name' => 'Storage 1', 'description' => 'Storage belakang'],
        ];

        foreach ($tokoRacks as $rack) {
            Rack::create(array_merge($rack, ['warehouse_id' => $toko->id]));
        }

        $this->command->info('Warehouses and Racks created successfully!');
    }
}
