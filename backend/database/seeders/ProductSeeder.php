<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Unit;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all()->keyBy('code');
        $units = Unit::all()->keyBy('code');

        $productsByCategory = [
            'SEM' => [ // Semen & Perekat - base unit SAK
                ['SEM-001', 'Semen Gresik 40 kg', 62000, 'SAK'],
                ['SEM-002', 'Semen Tiga Roda 40 kg', 58500, 'SAK'],
                ['SEM-003', 'Semen Padang 40 kg', 57500, 'SAK'],
                ['SEM-004', 'Semen Bosowa 40 kg', 56000, 'SAK'],
                ['SEM-005', 'Mortar Utama MU-100 40 kg', 68500, 'SAK'],
                ['SEM-006', 'Mortar Utama MU-200 40 kg', 72000, 'SAK'],
                ['SEM-007', 'Mortar Utama MU-300 40 kg', 75000, 'SAK'],
                ['SEM-008', 'Perekat Keramik Granit 25 kg', 78000, 'SAK'],
                ['SEM-009', 'Plester Aci Putih 40 kg', 52000, 'SAK'],
                ['SEM-010', 'Semen Putih 5 kg', 35000, 'SAK'],
                ['SEM-011', 'Nat Keramik 5 kg', 28000, 'SAK'],
                ['SEM-012', 'Waterproof Coating 5 kg', 95000, 'SAK'],
            ],
            'CAT' => [ // Cat & Finishing - base unit LTR atau KAL
                ['CAT-001', 'Cat Tembok Avian 5L', 185000, 'LTR'],
                ['CAT-002', 'Cat Tembok Dulux 5L', 220000, 'LTR'],
                ['CAT-003', 'Cat Tembok Nippon 5L', 195000, 'LTR'],
                ['CAT-004', 'Cat Tembok Jotun 5L', 210000, 'LTR'],
                ['CAT-005', 'Plamir Avian 25 kg', 85000, 'SAK'],
                ['CAT-006', 'Thinner A 5L', 65000, 'LTR'],
                ['CAT-007', 'Cat Kayu Melamine 1L', 95000, 'LTR'],
                ['CAT-008', 'Cat Besi 1 kg', 45000, 'KG'],
                ['CAT-009', 'Cat Dasar Tembok 1L', 42000, 'LTR'],
                ['CAT-010', 'Politur Kayu 1L', 78000, 'LTR'],
                ['CAT-011', 'Dempul Kayu 1 kg', 35000, 'KG'],
                ['CAT-012', 'Dempul Tembok 5 kg', 45000, 'KG'],
            ],
            'KRM' => [ // Keramik & Lantai - base unit M2 atau DUS
                ['KRM-001', 'Keramik 30x30 Putih Polos', 45000, 'M2'],
                ['KRM-002', 'Keramik 30x30 Motif Marmer', 55000, 'M2'],
                ['KRM-003', 'Keramik 40x40 Granit', 85000, 'M2'],
                ['KRM-004', 'Keramik 60x60 Premium', 125000, 'M2'],
                ['KRM-005', 'Keramik Dinding 20x25', 32000, 'M2'],
                ['KRM-006', 'Granit 60x60 Import', 185000, 'M2'],
                ['KRM-007', 'Vinyl Lantai 2mm', 95000, 'M2'],
                ['KRM-008', 'Vinyl Lantai 3mm', 135000, 'M2'],
                ['KRM-009', 'Parket Kayu 90x15', 165000, 'M2'],
                ['KRM-010', 'List Keramik 2.5m', 8500, 'MTR'],
                ['KRM-011', 'Nat Keramik Warna 5 kg', 42000, 'SAK'],
                ['KRM-012', 'Marmer Lokal 60x60', 220000, 'M2'],
            ],
            'PJR' => [ // Pintu & Jendela - base unit PCS
                ['PJR-001', 'Pintu Kayu Kamar Standard', 450000, 'PCS'],
                ['PJR-002', 'Pintu Kayu Kamar Minimalis', 550000, 'PCS'],
                ['PJR-003', 'Pintu Lipat PVC', 380000, 'PCS'],
                ['PJR-004', 'Pintu Aluminium Kaca', 650000, 'PCS'],
                ['PJR-005', 'Jendela Aluminium 2 daun', 420000, 'PCS'],
                ['PJR-006', 'Jendela Kayu 2 daun', 350000, 'PCS'],
                ['PJR-007', 'Kusen Kayu Pintu Standard', 280000, 'PCS'],
                ['PJR-008', 'Kusen Aluminium', 185000, 'MTR'],
                ['PJR-009', 'Engsel Pintu 4 inch', 25000, 'PCS'],
                ['PJR-010', 'Kunci Kamar Standard', 45000, 'PCS'],
                ['PJR-011', 'Handle Pintu Set', 85000, 'SET'],
                ['PJR-012', 'Pintu Garasi Besi', 1250000, 'PCS'],
            ],
            'BSI' => [ // Besi & Baja - base unit BTG atau KG
                ['BSI-001', 'Besi Beton 10 mm per batang', 85000, 'BTG'],
                ['BSI-002', 'Besi Beton 12 mm per batang', 115000, 'BTG'],
                ['BSI-003', 'Besi Beton 8 mm per batang', 45000, 'BTG'],
                ['BSI-004', 'Hollow 4x2 cm', 65000, 'BTG'],
                ['BSI-005', 'Hollow 4x4 cm', 85000, 'BTG'],
                ['BSI-006', 'Plat Besi 1.2mm 4x8', 850000, 'LBR'],
                ['BSI-007', 'Siku 4x4', 85000, 'BTG'],
                ['BSI-008', 'Wiremesh M8', 185000, 'LBR'],
                ['BSI-009', 'Kawat Bendrat 1 kg', 22000, 'KG'],
                ['BSI-010', 'Paku Beton 5 kg', 45000, 'KG'],
                ['BSI-011', 'Besi Strip 2 cm', 35000, 'BTG'],
                ['BSI-012', 'Plat Bordes 1m', 125000, 'MTR'],
            ],
            'KAY' => [ // Kayu - base unit LBR, BTG, M3
                ['KAY-001', 'Triplek 3mm 122x244', 95000, 'LBR'],
                ['KAY-002', 'Triplek 6mm 122x244', 145000, 'LBR'],
                ['KAY-003', 'Multiplek 9mm', 185000, 'LBR'],
                ['KAY-004', 'Papan Kayu Kamper 2x20', 85000, 'MTR'],
                ['KAY-005', 'Papan Kayu Meranti', 65000, 'MTR'],
                ['KAY-006', 'Kaso 5x7', 45000, 'MTR'],
                ['KAY-007', 'Reng 2x3', 15000, 'MTR'],
                ['KAY-008', 'Gypsum 9mm 120x240', 55000, 'LBR'],
                ['KAY-009', 'Gypsum 12mm 120x240', 72000, 'LBR'],
                ['KAY-010', 'Lis Gypsum 3m', 18000, 'MTR'],
                ['KAY-011', 'Blockboard 18mm', 420000, 'LBR'],
                ['KAY-012', 'MDF 18mm 122x244', 385000, 'LBR'],
            ],
            'PIP' => [ // Pipa & Sanitasi - base unit MTR atau PCS
                ['PIP-001', 'Pipa PVC 4 inch', 45000, 'MTR'],
                ['PIP-002', 'Pipa PVC 2 inch', 22000, 'MTR'],
                ['PIP-003', 'Pipa PVC 3 inch', 32000, 'MTR'],
                ['PIP-004', 'Pipa PPR 1/2 inch', 35000, 'MTR'],
                ['PIP-005', 'Pipa PPR 3/4 inch', 48000, 'MTR'],
                ['PIP-006', 'Fitting PVC Elbow 4 inch', 25000, 'PCS'],
                ['PIP-007', 'Fitting PVC Tee 4 inch', 35000, 'PCS'],
                ['PIP-008', 'Kran Cuci Tangan', 85000, 'PCS'],
                ['PIP-009', 'Closet Duduk Standard', 450000, 'PCS'],
                ['PIP-010', 'Closet Jongkok', 185000, 'PCS'],
                ['PIP-011', 'Shower Set', 125000, 'SET'],
                ['PIP-012', 'Pipa Galvanis 2 inch', 85000, 'MTR'],
            ],
            'LST' => [ // Listrik - base unit MTR atau PCS
                ['LST-001', 'Kabel NYM 2.5 mm', 12000, 'MTR'],
                ['LST-002', 'Kabel NYM 1.5 mm', 7500, 'MTR'],
                ['LST-003', 'Kabel NYM 4 mm', 18500, 'MTR'],
                ['LST-004', 'Stop Kontak 2 lubang', 25000, 'PCS'],
                ['LST-005', 'Saklar 1 tunggal', 15000, 'PCS'],
                ['LST-006', 'Saklar 2 ganda', 22000, 'PCS'],
                ['LST-007', 'MCB 2 Pole 6A', 45000, 'PCS'],
                ['LST-008', 'MCB 2 Pole 10A', 48000, 'PCS'],
                ['LST-009', 'Kabel Ties 100 pcs', 15000, 'PAK'],
                ['LST-010', 'Pipa Conduit 1/2 inch', 12000, 'MTR'],
                ['LST-011', 'Fitting Lampu LED', 8500, 'PCS'],
                ['LST-012', 'Box MCB 4 group', 35000, 'PCS'],
            ],
            'LMP' => [ // Lampu - base unit PCS
                ['LMP-001', 'Lampu LED Bulb 9W', 25000, 'PCS'],
                ['LMP-002', 'Lampu LED Bulb 15W', 35000, 'PCS'],
                ['LMP-003', 'Lampu LED Downlight 12W', 45000, 'PCS'],
                ['LMP-004', 'Lampu LED Panel 18W', 65000, 'PCS'],
                ['LMP-005', 'Lampu LED Strip 5m', 85000, 'ROL'],
                ['LMP-006', 'Lampu Neon TL 18W', 28000, 'PCS'],
                ['LMP-007', 'Lampu Neon TL 36W', 35000, 'PCS'],
                ['LMP-008', 'Ballast Neon 36W', 45000, 'PCS'],
                ['LMP-009', 'Lampu Emergency', 95000, 'PCS'],
                ['LMP-010', 'Reflektor LED 50W', 125000, 'PCS'],
                ['LMP-011', 'Lampu Taman LED', 35000, 'PCS'],
                ['LMP-012', 'Lampu Sorot LED 20W', 55000, 'PCS'],
            ],
            'ATB' => [ // Alat & Perlengkapan - base unit PCS
                ['ATB-001', 'Palu 500g', 45000, 'PCS'],
                ['ATB-002', 'Obeng Set 6 pcs', 85000, 'SET'],
                ['ATB-003', 'Tang Kombinasi', 55000, 'PCS'],
                ['ATB-004', 'Bor Listrik 10mm', 285000, 'PCS'],
                ['ATB-005', 'Gerinda Tangan', 350000, 'PCS'],
                ['ATB-006', 'Meteran 5m', 45000, 'PCS'],
                ['ATB-007', 'Meteran 3m', 28000, 'PCS'],
                ['ATB-008', 'Gergaji Kayu', 65000, 'PCS'],
                ['ATB-009', 'Kunci Inggris 12 inch', 75000, 'PCS'],
                ['ATB-010', 'Kunci Ring Set', 95000, 'SET'],
                ['ATB-011', 'Sekop', 45000, 'PCS'],
                ['ATB-012', 'Ember Cor 1/2', 25000, 'PCS'],
            ],
            'PNG' => [ // Pengaman & Safety - base unit PCS
                ['PNG-001', 'Helm Proyek', 45000, 'PCS'],
                ['PNG-002', 'Sarung Tangan Karet', 15000, 'PAK'],
                ['PNG-003', 'Masker Debu', 25000, 'PAK'],
                ['PNG-004', 'Sepatu Safety', 185000, 'PCS'],
                ['PNG-005', 'Kacamata Safety', 35000, 'PCS'],
                ['PNG-006', 'Rompi Safety', 45000, 'PCS'],
                ['PNG-007', 'Tali Safety', 85000, 'PCS'],
                ['PNG-008', 'Ear Plug', 15000, 'PAK'],
                ['PNG-009', 'Pelindung Wajah', 55000, 'PCS'],
                ['PNG-010', 'Apron Kerja', 35000, 'PCS'],
            ],
            'FIN' => [ // Finishing & Dekorasi - base unit MTR atau LBR
                ['FIN-001', 'Lis PVC 3m', 25000, 'MTR'],
                ['FIN-002', 'Rolf 2 inch', 15000, 'MTR'],
                ['FIN-003', 'Wallpaper Roll', 85000, 'ROL'],
                ['FIN-004', 'Gipsum Board 9mm', 52000, 'LBR'],
                ['FIN-005', 'Lis Gypsum Sudut', 12000, 'MTR'],
                ['FIN-006', 'Perekat Gipsum', 35000, 'SAK'],
                ['FIN-007', 'Plafon PVC', 45000, 'LBR'],
                ['FIN-008', 'Kaca Cermin 3mm', 185000, 'M2'],
                ['FIN-009', 'Kaca Bening 5mm', 125000, 'M2'],
                ['FIN-010', 'Aluminium Foil', 25000, 'ROL'],
            ],
            'BAT' => [ // Bata & Batako - base unit PCS
                ['BAT-001', 'Bata Merah Press', 650, 'PCS'],
                ['BAT-002', 'Bata Merah Oven', 550, 'PCS'],
                ['BAT-003', 'Batako Putih', 3500, 'PCS'],
                ['BAT-004', 'Batako Semen', 4200, 'PCS'],
                ['BAT-005', 'Hebel 7.5 cm', 85000, 'M2'],
                ['BAT-006', 'Hebel 10 cm', 95000, 'M2'],
                ['BAT-007', 'Paving Block 6 cm', 8500, 'M2'],
                ['BAT-008', 'Paving Block 8 cm', 9500, 'M2'],
                ['BAT-009', 'Kansteen', 8500, 'MTR'],
                ['BAT-010', 'Grass Block', 45000, 'M2'],
            ],
            'PAS' => [ // Pasir & Batu - base unit M3 atau SAK
                ['PAS-001', 'Pasir Pasang', 250000, 'M3'],
                ['PAS-002', 'Pasir Beton', 285000, 'M3'],
                ['PAS-003', 'Batu Split 1/2', 320000, 'M3'],
                ['PAS-004', 'Batu Split 2/3', 350000, 'M3'],
                ['PAS-005', 'Batu Koral', 280000, 'M3'],
                ['PAS-006', 'Sirtu', 180000, 'M3'],
                ['PAS-007', 'Pasir Urug', 150000, 'M3'],
                ['PAS-008', 'Batu Belah', 265000, 'M3'],
            ],
            'LAI' => [ // Lain-lain
                ['LAI-001', 'Lem Kayu 1 kg', 35000, 'KG'],
                ['LAI-002', 'Lem PVC 1 kg', 45000, 'KG'],
                ['LAI-003', 'Silikon Sealant', 28000, 'BTL'],
                ['LAI-004', 'Busa Expand', 35000, 'BTL'],
                ['LAI-005', 'Plastik Cor', 8500, 'MTR'],
                ['LAI-006', 'Kawat Bendrat Roll', 185000, 'ROL'],
                ['LAI-007', 'Paku 2 inch 1 kg', 22000, 'KG'],
                ['LAI-008', 'Paku 3 inch 1 kg', 25000, 'KG'],
                ['LAI-009', 'Baut + Mur Set', 45000, 'SET'],
                ['LAI-010', 'Dempul Aluminium', 35000, 'BTL'],
            ],
        ];

        $codeUsed = [];
        foreach ($productsByCategory as $catCode => $products) {
            $category = $categories->get($catCode);
            if (!$category) continue;

            foreach ($products as $idx => $item) {
                [$code, $name, $basePrice, $unitCode] = $item;
                if (isset($codeUsed[$code])) continue;
                $codeUsed[$code] = true;

                $unit = $units->get($unitCode);
                if (!$unit) continue;

                $product = Product::updateOrCreate(
                    ['code' => $code],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'description' => 'Produk toko bahan bangunan - ' . $name,
                        'base_unit_id' => $unit->id,
                        'base_price' => $basePrice,
                        'minimum_stock' => in_array($unitCode, ['M3', 'SAK', 'BTG']) ? 5 : 10,
                        'is_active' => true,
                    ]
                );

                ProductUnit::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'unit_id' => $unit->id,
                    ],
                    [
                        'conversion_factor' => 1,
                        'selling_price' => $basePrice,
                        'is_default' => true,
                    ]
                );
            }
        }

        $this->seedMultiUnits($units);
        $this->command->info('Products (toko bahan bangunan) created successfully!');
    }

    /**
     * Tambah satuan alternatif (multisatuan) untuk produk yang relevan.
     * Format: [unit_code, conversion_factor ke base unit, selling_price]
     * conversion_factor = berapa base unit per 1 satuan ini. Contoh: 1 ROL = 100 MTR → factor 100.
     */
    private function seedMultiUnits($units): void
    {
        $multiUnits = [
            // Semen: base SAK (40 kg), tambah jual per KG. 1 KG = 1/40 SAK
            'SEM-001' => [['KG', 0.025, 1620]],   // 62000/40 + margin
            'SEM-002' => [['KG', 0.025, 1520]],
            'SEM-003' => [['KG', 0.025, 1500]],
            'SEM-004' => [['KG', 0.025, 1450]],
            'SEM-005' => [['KG', 0.025, 1750]],
            'SEM-006' => [['KG', 0.025, 1850]],
            'SEM-007' => [['KG', 0.025, 1920]],
            'SEM-008' => [['KG', 0.04, 3200]],   // 25 kg/sak
            'SEM-009' => [['KG', 0.025, 1350]],
            // Cat: base LTR, tambah Kaleng 5L. 1 KAL = 5 LTR
            'CAT-001' => [['KAL', 5, 185000]],
            'CAT-002' => [['KAL', 5, 220000]],
            'CAT-003' => [['KAL', 5, 195000]],
            'CAT-004' => [['KAL', 5, 210000]],
            'CAT-006' => [['KAL', 5, 65000]],
            // Kabel: base MTR, tambah Roll 100m. 1 ROL = 100 MTR
            'LST-001' => [['ROL', 100, 1150000]],  // 100 x 12000 kurang dikit
            'LST-002' => [['ROL', 100, 720000]],
            'LST-003' => [['ROL', 100, 1780000]],
            'LST-010' => [['ROL', 100, 1150000]],  // Pipa conduit 100m/roll
            // Pipa PVC: base MTR, tambah Batang 4m. 1 BTG = 4 MTR
            'PIP-001' => [['BTG', 4, 175000]],
            'PIP-002' => [['BTG', 4, 85000]],
            'PIP-003' => [['BTG', 4, 125000]],
            'PIP-004' => [['BTG', 4, 135000]],
            'PIP-005' => [['BTG', 4, 188000]],
            'PIP-012' => [['BTG', 6, 500000]],    // Pipa galvanis 6m/batang
            // Besi beton: base BTG (12m), tambah per MTR. 1 MTR = 1/12 BTG (panjang), atau per KG
            // Untuk besi: 1 batang 12m ≈ 12 m, jadi 1 MTR = 1/12 BTG
            'BSI-001' => [['MTR', 0.0833, 7100]],   // 85000/12
            'BSI-002' => [['MTR', 0.0833, 9600]],
            'BSI-003' => [['MTR', 0.0833, 3750]],
            'BSI-004' => [['MTR', 0.0833, 5400]],   // hollow 4m/batang asumsi
            'BSI-005' => [['MTR', 0.0833, 7100]],
            'BSI-007' => [['MTR', 0.0833, 7100]],  // siku 6m/batang
            'BSI-011' => [['MTR', 0.0833, 2900]],
            // Kayu: triplek/gypsum base LBR, tambah dus (isi 20 lembar). 1 DUS = 20 LBR
            'KAY-001' => [['DUS', 20, 1850000]],
            'KAY-002' => [['DUS', 20, 2820000]],
            'KAY-008' => [['DUS', 20, 1080000]],
            'KAY-009' => [['DUS', 20, 1400000]],
            // Keramik: base M2, tambah Karton/Dus. 30x30 = 1.44 m2/dus (12 pcs). 1 KRT = 1.44 M2
            'KRM-001' => [['KRT', 1.44, 63000]],
            'KRM-002' => [['KRT', 1.44, 78000]],
            'KRM-003' => [['KRT', 1.96, 165000]],  // 40x40, 1 dus ≈ 1.96 m2
            'KRM-005' => [['KRT', 1.2, 38000]],   // dinding 20x25
            // Lampu LED Strip: base ROL 5m, sudah roll
            'LMP-005' => [['MTR', 0.2, 17000]],   // 1 m = 0.2 roll (5m)
            // Paku/Kawat: base KG, tambah Pack. 1 PAK = 0.5 KG (asumsi)
            'BSI-009' => [['PAK', 0.5, 12000]],
            'BSI-010' => [['PAK', 1, 9000]],      // paku beton 1 kg/pak
            'LAI-007' => [['PAK', 0.5, 11500]],
            'LAI-008' => [['PAK', 0.5, 12500]],
            // Engsel/Kunci: base PCS, tambah Lusin (KODI=20). 1 KOD = 20 PCS
            'PJR-009' => [['KOD', 20, 480000]],
            'PJR-010' => [['KOD', 20, 860000]],
            // Bata: base PCS, tambah M3 (1 m3 ≈ 500 bata). 1 M3 = 500 PCS
            'BAT-001' => [['M3', 500, 320000]],
            'BAT-002' => [['M3', 500, 270000]],
            'BAT-003' => [['M3', 250, 860000]],   // batako lebih besar
            'BAT-004' => [['M3', 250, 1030000]],
            // Pasir: base M3, tambah Colt (1 colt ≈ 2.5 m3) atau BAG. 1 BAG = 0.05 M3 (karung 50L)
            'PAS-001' => [['BAG', 0.05, 13500]],
            'PAS-002' => [['BAG', 0.05, 15000]],
            // Lem/Sealant: base BTL, tambah Lusin. 1 DZN = 12 BTL (unit DZN might not exist - we have KOD 20)
            // Nat Keramik: base SAK 5kg, tambah KG
            'KRM-011' => [['KG', 0.2, 8500]],    // 5 kg/sak
            'SEM-011' => [['KG', 0.2, 5600]],
            'SEM-012' => [['KG', 0.2, 19000]],
        ];

        foreach ($multiUnits as $productCode => $extraUnits) {
            $product = Product::where('code', $productCode)->first();
            if (!$product) continue;

            foreach ($extraUnits as $triple) {
                [$unitCode, $factor, $sellingPrice] = $triple;
                $unit = $units->get($unitCode);
                if (!$unit) continue;

                ProductUnit::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'unit_id' => $unit->id,
                    ],
                    [
                        'conversion_factor' => $factor,
                        'selling_price' => $sellingPrice,
                        'is_default' => false,
                    ]
                );
            }
        }

        $this->command->info('Multisatuan produk (product_units) seeded successfully!');
    }
}
