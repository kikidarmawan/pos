<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'SEM', 'name' => 'Semen & Perekat', 'description' => 'Semen, mortar, perekat keramik, plester'],
            ['code' => 'CAT', 'name' => 'Cat & Finishing', 'description' => 'Cat tembok, cat kayu, thinner, plamir'],
            ['code' => 'KRM', 'name' => 'Keramik & Lantai', 'description' => 'Keramik lantai/dinding, granit, marmer, vinyl'],
            ['code' => 'PJR', 'name' => 'Pintu & Jendela', 'description' => 'Pintu kayu/aluminium, jendela, kusen'],
            ['code' => 'BSI', 'name' => 'Besi & Baja', 'description' => 'Besi beton, hollow, plat, siku, wiremesh'],
            ['code' => 'KAY', 'name' => 'Kayu', 'description' => 'Kayu olahan, triplek, multiplek, papan'],
            ['code' => 'PIP', 'name' => 'Pipa & Sanitasi', 'description' => 'Pipa PVC/PPR/besi, fitting, kran, closet'],
            ['code' => 'LST', 'name' => 'Listrik', 'description' => 'Kabel, stop kontak, saklar, MCB, fitting lampu'],
            ['code' => 'LMP', 'name' => 'Lampu', 'description' => 'Lampu LED, neon, downlight, perlengkapan'],
            ['code' => 'ATB', 'name' => 'Alat & Perlengkapan', 'description' => 'Palu, obeng, bor, gerinda, kunci, meteran'],
            ['code' => 'PNG', 'name' => 'Pengaman & Safety', 'description' => 'Sarung tangan, helm, masker, sepatu safety'],
            ['code' => 'FIN', 'name' => 'Finishing & Dekorasi', 'description' => 'Lis, rolf, wallpaper, gipsum'],
            ['code' => 'BAT', 'name' => 'Bata & Batako', 'description' => 'Bata merah, batako, hebel, paving'],
            ['code' => 'PAS', 'name' => 'Pasir & Batu', 'description' => 'Pasir, batu split, batu koral, sirtu'],
            ['code' => 'LAI', 'name' => 'Lain-lain', 'description' => 'Produk bahan bangunan lainnya'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }

        $this->command->info('Categories (toko bahan bangunan) created successfully!');
    }
}
