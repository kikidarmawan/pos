# 🔧 Backend Requirements - POS System

## File Backend yang Diperlukan

Untuk frontend Vue.js yang sudah dibuat bisa berfungsi, backend Laravel Anda perlu memiliki file-file berikut:

---

## 1️⃣ Database Migrations

Buat file migrations berikut di `backend/database/migrations/`:

### Core Tables:
- [x] `2024_01_01_000000_create_users_table.php` (sudah ada)
- [ ] `2024_01_01_000001_create_permission_tables.php` (Spatie)
- [ ] `2024_01_01_000002_create_categories_table.php`
- [ ] `2024_01_01_000003_create_units_table.php`
- [ ] `2024_01_01_000004_create_warehouses_table.php`
- [ ] `2024_01_01_000005_create_suppliers_table.php`
- [ ] `2024_01_01_000006_create_products_table.php` ⭐ **PENTING**
- [ ] `2024_01_01_000007_create_purchases_table.php`
- [ ] `2024_01_01_000008_create_sales_table.php`
- [ ] `2024_01_01_000009_create_stocks_table.php` ⭐ **PENTING**

### Key Fields untuk Multi-Satuan:

**products table:**
```php
$table->foreignId('base_unit_id')->constrained('units');
$table->decimal('base_price', 15, 2);
```

**product_units table:** ⭐ **KUNCI FITUR MULTI-SATUAN**
```php
$table->foreignId('product_id')->constrained()->onDelete('cascade');
$table->foreignId('unit_id')->constrained()->onDelete('cascade');
$table->decimal('conversion_factor', 15, 3); // 1 Roll = 100 Meter
$table->decimal('selling_price', 15, 2);     // Harga jual per satuan
$table->string('barcode')->nullable();
$table->boolean('is_default')->default(false);
```

**stocks table:** ⭐ **UNTUK RAK & GUDANG**
```php
$table->foreignId('product_id')->constrained();
$table->foreignId('warehouse_id')->constrained();
$table->foreignId('rack_id')->nullable()->constrained();
$table->decimal('quantity', 15, 3); // Dalam base unit
$table->unique(['product_id', 'warehouse_id', 'rack_id']);
```

---

## 2️⃣ Models

Buat file models di `backend/app/Models/`:

- [ ] `User.php` (sudah ada, tambahkan `HasRoles` trait)
- [ ] `Category.php`
- [ ] `Unit.php`
- [ ] `Warehouse.php`
- [ ] `Rack.php`
- [ ] `Supplier.php`
- [ ] `Product.php` dengan relationship `productUnits()`
- [ ] `ProductUnit.php` ⭐ dengan methods:
  - `toBaseUnit($quantity)` - Convert ke base unit
  - `fromBaseUnit($quantity)` - Convert dari base unit
- [ ] `Purchase.php`
- [ ] `PurchaseDetail.php`
- [ ] `Sale.php`
- [ ] `SaleDetail.php`
- [ ] `Stock.php`
- [ ] `StockMovement.php`

---

## 3️⃣ Controllers

Buat file controllers di `backend/app/Http/Controllers/Api/`:

### Required Controllers:
- [ ] `AuthController.php` - login, logout, me, changePassword
- [ ] `UserController.php` - CRUD users
- [ ] `RoleController.php` - CRUD roles & permissions
- [ ] `CategoryController.php` - CRUD categories
- [ ] `UnitController.php` - CRUD units
- [ ] `WarehouseController.php` - CRUD warehouses
- [ ] `RackController.php` - CRUD racks
- [ ] `SupplierController.php` - CRUD suppliers
- [ ] `ProductController.php` ⭐ - CRUD products dengan multi-unit
- [ ] `PurchaseController.php` ⭐ - Create purchase, auto stock update
- [ ] `SaleController.php` ⭐ - Create sale, auto stock deduction
- [ ] `StockController.php` - View stocks, adjustment
- [ ] `ReportController.php` ⭐ - Semua laporan & export

### Key Logic:

**ProductController:**
```php
public function store(Request $request) {
    // Save product
    // Save multiple product_units dengan conversion_factor
}
```

**PurchaseController:**
```php
public function store(Request $request) {
    // Create purchase
    // Foreach detail:
    //   - Convert quantity ke base unit
    //   - Update stock
    //   - Create stock movement
}
```

**SaleController:**
```php
public function store(Request $request) {
    // Validate stock availability
    // Create sale
    // Foreach detail:
    //   - Convert quantity ke base unit
    //   - Deduct stock (FIFO)
    //   - Create stock movement
}
```

---

## 4️⃣ Exports

Buat file exports di `backend/app/Exports/`:

- [ ] `SalesReportExport.php`
- [ ] `PurchasesReportExport.php`
- [ ] `StockReportExport.php`

---

## 5️⃣ Seeders

Buat file seeders di `backend/database/seeders/`:

- [ ] `DatabaseSeeder.php` - Main seeder
- [ ] `RolePermissionSeeder.php` - 4 roles + permissions
- [ ] `UserSeeder.php` - 4 demo users
- [ ] `CategorySeeder.php` - Sample categories
- [ ] `UnitSeeder.php` - Sample units (Pcs, Box, Meter, Roll, dll)
- [ ] `WarehouseSeeder.php` - Sample warehouses & racks
- [ ] `SupplierSeeder.php` - Sample suppliers

---

## 6️⃣ Routes

File: `backend/routes/api.php` ✅ (sudah dibuat)

Endpoints yang harus ada:
- POST `/api/login`
- GET `/api/me`
- GET/POST/PUT/DELETE `/api/products`
- GET/POST `/api/purchases`
- GET/POST `/api/sales`
- GET `/api/stocks`
- GET `/api/reports/dashboard`
- GET `/api/reports/sales`
- GET `/api/reports/export/sales`
- Dan 30+ endpoints lainnya

---

## 7️⃣ Config Files

Files yang perlu dikonfigurasi:

- [x] `config/cors.php` ✅ (sudah ada)
- [x] `config/sanctum.php` ✅ (sudah ada)
- [x] `config/permission.php` ✅ (sudah ada)
- [ ] `config/filesystems.php` - untuk upload image produk

---

## 🤔 Apakah Backend Anda Sudah Lengkap?

Cek apakah backend Anda sudah punya:

```bash
cd backend

# Check migrations
ls database/migrations/

# Check models
ls app/Models/

# Check controllers
ls app/Http/Controllers/Api/

# Check seeders
ls database/seeders/
```

---

## 🚨 PENTING!

Jika backend Anda belum punya file-file di atas, **SAYA BISA BUATKAN SEMUA!**

Tinggal bilang:
- "Buatkan semua migrations"
- "Buatkan semua models"
- "Buatkan semua controllers"
- Atau "Buatkan semua file backend yang diperlukan"

---

## 📦 Dependencies Backend

Pastikan `composer.json` punya:

```json
{
  "require": {
    "php": "^8.1",
    "laravel/framework": "^10.10",
    "laravel/sanctum": "^3.3",
    "spatie/laravel-permission": "^6.0",
    "maatwebsite/excel": "^3.1"
  }
}
```

Install dengan: `composer install`

---

## ✅ Next Steps

1. **Cek backend Anda** - Apakah sudah punya file POS?
2. **Jika belum** - Saya buatkan semua file backend
3. **Jika sudah** - Tinggal `php artisan migrate --seed`
4. **Test frontend** - `cd frontend && npm run dev`

---

**Mau saya buatkan semua file backend sekarang?** 🚀
