<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@pos.com',
            'password' => Hash::make('password'),
        ]);
        $superAdmin->assignRole('Super Admin');

        // Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.user@pos.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('Admin');

        // Kasir
        $kasir = User::create([
            'name' => 'Kasir',
            'email' => 'kasir@pos.com',
            'password' => Hash::make('password'),
        ]);
        $kasir->assignRole('Kasir');

        // Gudang
        $gudang = User::create([
            'name' => 'Staff Gudang',
            'email' => 'gudang@pos.com',
            'password' => Hash::make('password'),
        ]);
        $gudang->assignRole('Gudang');

        $this->command->info('Users created successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('- admin@pos.com / password (Super Admin)');
        $this->command->info('- admin.user@pos.com / password (Admin)');
        $this->command->info('- kasir@pos.com / password (Kasir)');
        $this->command->info('- gudang@pos.com / password (Gudang)');
    }
}
