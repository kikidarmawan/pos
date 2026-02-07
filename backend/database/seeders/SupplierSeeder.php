<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'SUP-001',
                'name' => 'PT Elektro Jaya',
                'contact_person' => 'Budi Santoso',
                'phone' => '021-11111111',
                'email' => 'elektro@example.com',
                'address' => 'Jl. Industri Raya No. 100, Jakarta',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-002',
                'name' => 'CV Kabel Indonesia',
                'contact_person' => 'Siti Aminah',
                'phone' => '021-22222222',
                'email' => 'kabel@example.com',
                'address' => 'Jl. Perdagangan No. 50, Bekasi',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-003',
                'name' => 'Toko Lampu Terang',
                'contact_person' => 'Ahmad Yani',
                'phone' => '021-33333333',
                'email' => 'lampu@example.com',
                'address' => 'Jl. Cahaya No. 25, Tangerang',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-004',
                'name' => 'PT Instalasi Prima',
                'contact_person' => 'Dewi Lestari',
                'phone' => '021-44444444',
                'email' => 'instalasi@example.com',
                'address' => 'Jl. Teknologi No. 75, Bogor',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }

        $this->command->info('Suppliers created successfully!');
    }
}
