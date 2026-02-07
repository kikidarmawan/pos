<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'ELK', 'name' => 'Elektronik', 'description' => 'Produk elektronik'],
            ['code' => 'KBL', 'name' => 'Kabel & Instalasi', 'description' => 'Kabel listrik dan instalasi'],
            ['code' => 'LMP', 'name' => 'Lampu', 'description' => 'Lampu dan perlengkapan'],
            ['code' => 'SKL', 'name' => 'Saklar & Stop Kontak', 'description' => 'Saklar dan stop kontak'],
            ['code' => 'ATK', 'name' => 'Alat Tulis Kantor', 'description' => 'Peralatan tulis kantor'],
            ['code' => 'PKG', 'name' => 'Packing & Kemasan', 'description' => 'Bahan packing dan kemasan'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Categories created successfully!');
    }
}
