<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',

            // Role management
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',

            // Category
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',

            // Unit
            'view_units',
            'create_units',
            'edit_units',
            'delete_units',

            // Warehouse
            'view_warehouses',
            'create_warehouses',
            'edit_warehouses',
            'delete_warehouses',

            // Supplier
            'view_suppliers',
            'create_suppliers',
            'edit_suppliers',
            'delete_suppliers',

            // Product
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',

            // Purchase
            'view_purchases',
            'create_purchases',
            'cancel_purchases',

            // Sale
            'view_sales',
            'create_sales',
            'cancel_sales',

            // Stock
            'view_stocks',
            'adjust_stocks',

            // Reports
            'view_reports',
            'export_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - all permissions
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - manage master data & reports
        $admin = Role::create(['name' => 'Admin']);
        $admin->givePermissionTo([
            'view_users',
            'create_users',
            'edit_users',
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',
            'view_units',
            'create_units',
            'edit_units',
            'delete_units',
            'view_warehouses',
            'create_warehouses',
            'edit_warehouses',
            'delete_warehouses',
            'view_suppliers',
            'create_suppliers',
            'edit_suppliers',
            'delete_suppliers',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'view_purchases',
            'view_sales',
            'view_stocks',
            'view_reports',
            'export_reports',
        ]);

        // Kasir - POS & view only
        $kasir = Role::create(['name' => 'Kasir']);
        $kasir->givePermissionTo([
            'view_products',
            'create_sales',
            'view_sales',
            'view_stocks',
        ]);

        // Gudang - purchases & stock management
        $gudang = Role::create(['name' => 'Gudang']);
        $gudang->givePermissionTo([
            'view_products',
            'view_purchases',
            'create_purchases',
            'view_stocks',
            'adjust_stocks',
            'view_suppliers',
            'view_warehouses',
        ]);

        $this->command->info('Roles and Permissions created successfully!');
    }
}
