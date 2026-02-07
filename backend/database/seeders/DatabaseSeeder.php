<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            UnitSeeder::class,
            WarehouseSeeder::class,
            SupplierSeeder::class,
            StoreSeeder::class,
        ]);
    }
}
