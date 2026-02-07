# Refactor: `if` → `when()` untuk Query Builder

## ✅ Perubahan

Semua conditional query menggunakan `when()` method Laravel untuk kode yang lebih clean dan fluent.

---

## 🎯 Keuntungan Pakai `when()`

### ❌ **Sebelum (pakai `if`):**
```php
$query = Product::query();

if ($request->search) {
    $query->where('name', 'like', '%' . $request->search . '%');
}

if ($request->category_id) {
    $query->where('category_id', $request->category_id);
}

$products = $query->latest()->paginate(15);
```

### ✅ **Sesudah (pakai `when()`):**
```php
$products = Product::query()
    ->when($request->search, function ($query, $search) {
        $query->where('name', 'like', '%' . $search . '%');
    })
    ->when($request->category_id, function ($query, $categoryId) {
        $query->where('category_id', $categoryId);
    })
    ->latest()
    ->paginate(15);
```

### 🔥 **Keuntungan:**
1. **Lebih Fluent** - Method chaining lebih clean
2. **Lebih Readable** - Lebih mudah dibaca top-to-bottom
3. **Automatic Null Check** - `when()` otomatis skip jika value falsy
4. **Immutable Variable** - Tidak perlu `$query` variable yang di-mutate
5. **Best Practice Laravel** - Sesuai Laravel convention

---

## 📁 File yang Diubah

### 1. **ProductController.php** ✅
```php
$products = Product::with(['category', 'baseUnit', 'productUnits.unit'])
    ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%')
                ->orWhere('barcode', 'like', '%' . $search . '%');
        });
    })
    ->when($request->category_id, function ($query, $categoryId) {
        $query->where('category_id', $categoryId);
    })
    ->when($request->has('is_active'), function ($query) use ($request) {
        $query->where('is_active', $request->is_active);
    })
    ->withSum('stocks as total_stock', 'quantity')
    ->latest()
    ->paginate($request->per_page ?? 15);
```

### 2. **CategoryController.php** ✅
```php
$categories = Category::query()
    ->when($request->search, function ($query, $search) {
        $query->where('name', 'like', '%' . $search . '%')
            ->orWhere('code', 'like', '%' . $search . '%');
    })
    ->when($request->has('is_active'), function ($query) use ($request) {
        $query->where('is_active', $request->is_active);
    })
    ->latest()
    ->paginate($request->per_page ?? 15);
```

### 3. **UnitController.php** ✅
```php
$units = Unit::query()
    ->when($request->search, function ($query, $search) {
        $query->where('name', 'like', '%' . $search . '%')
            ->orWhere('code', 'like', '%' . $search . '%');
    })
    ->when($request->has('is_active'), function ($query) use ($request) {
        $query->where('is_active', $request->is_active);
    })
    ->latest()
    ->paginate($request->per_page ?? 15);
```

### 4. **WarehouseController.php** ✅
```php
$warehouses = Warehouse::with('racks')
    ->when($request->search, function ($query, $search) {
        $query->where('name', 'like', '%' . $search . '%')
            ->orWhere('code', 'like', '%' . $search . '%');
    })
    ->when($request->has('is_active'), function ($query) use ($request) {
        $query->where('is_active', $request->is_active);
    })
    ->latest()
    ->paginate($request->per_page ?? 15);
```

### 5. **RackController.php** ✅
```php
$racks = Rack::with('warehouse')
    ->when($request->warehouse_id, function ($query, $warehouseId) {
        $query->where('warehouse_id', $warehouseId);
    })
    ->when($request->search, function ($query, $search) {
        $query->where('name', 'like', '%' . $search . '%')
            ->orWhere('code', 'like', '%' . $search . '%');
    })
    ->latest()
    ->paginate($request->per_page ?? 15);
```

### 6. **SupplierController.php** ✅
```php
$suppliers = Supplier::query()
    ->when($request->search, function ($query, $search) {
        $query->where('name', 'like', '%' . $search . '%')
            ->orWhere('code', 'like', '%' . $search . '%')
            ->orWhere('phone', 'like', '%' . $search . '%');
    })
    ->when($request->has('is_active'), function ($query) use ($request) {
        $query->where('is_active', $request->is_active);
    })
    ->latest()
    ->paginate($request->per_page ?? 15);
```

### 7. **UserController.php** ✅
```php
$users = User::with('roles')
    ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
        });
    })
    ->latest()
    ->paginate($request->per_page ?? 15);
```

### 8. **RoleController.php** ✅
```php
$roles = Role::with('permissions')
    ->when($request->search, function ($query, $search) {
        $query->where('name', 'like', '%' . $search . '%');
    })
    ->latest()
    ->paginate($request->per_page ?? 15);
```

### 9. **PurchaseController.php** ✅
```php
$purchases = Purchase::with(['supplier', 'warehouse', 'user'])
    ->when($request->search, function ($query, $search) {
        $query->where('invoice_number', 'like', '%' . $search . '%');
    })
    ->when($request->supplier_id, function ($query, $supplierId) {
        $query->where('supplier_id', $supplierId);
    })
    ->when($request->warehouse_id, function ($query, $warehouseId) {
        $query->where('warehouse_id', $warehouseId);
    })
    ->when($request->status, function ($query, $status) {
        $query->where('status', $status);
    })
    ->when($request->start_date, function ($query, $startDate) {
        $query->whereDate('purchase_date', '>=', $startDate);
    })
    ->when($request->end_date, function ($query, $endDate) {
        $query->whereDate('purchase_date', '<=', $endDate);
    })
    ->latest()
    ->paginate($request->per_page ?? 15);
```

### 10. **SaleController.php** ✅
```php
$sales = Sale::with(['warehouse', 'user'])
    ->when($request->search, function ($query, $search) {
        $query->where('invoice_number', 'like', '%' . $search . '%')
            ->orWhere('customer_name', 'like', '%' . $search . '%');
    })
    ->when($request->warehouse_id, function ($query, $warehouseId) {
        $query->where('warehouse_id', $warehouseId);
    })
    ->when($request->status, function ($query, $status) {
        $query->where('status', $status);
    })
    ->when($request->start_date, function ($query, $startDate) {
        $query->whereDate('sale_date', '>=', $startDate);
    })
    ->when($request->end_date, function ($query, $endDate) {
        $query->whereDate('sale_date', '<=', $endDate);
    })
    ->latest()
    ->paginate($request->per_page ?? 15);
```

### 11. **StockController.php** ✅
```php
$stocks = Stock::with(['product.category', 'product.baseUnit', 'warehouse', 'rack'])
    ->when($request->search, function ($query, $search) {
        $query->whereHas('product', function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%');
        });
    })
    ->when($request->warehouse_id, function ($query, $warehouseId) {
        $query->where('warehouse_id', $warehouseId);
    })
    ->when($request->rack_id, function ($query, $rackId) {
        $query->where('rack_id', $rackId);
    })
    ->when($request->category_id, function ($query, $categoryId) {
        $query->whereHas('product', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    })
    ->when($request->has('low_stock') && $request->low_stock, function ($query) {
        $query->whereHas('product', function ($q) {
            $q->whereRaw('stocks.quantity < products.minimum_stock');
        });
    })
    ->latest()
    ->paginate($request->per_page ?? 15);
```

---

## 📊 Summary

| Controller | Before | After | Status |
|-----------|--------|-------|--------|
| ProductController | `if` statements | `when()` method | ✅ |
| CategoryController | `if` statements | `when()` method | ✅ |
| UnitController | `if` statements | `when()` method | ✅ |
| WarehouseController | `if` statements | `when()` method | ✅ |
| RackController | `if` statements | `when()` method | ✅ |
| SupplierController | `if` statements | `when()` method | ✅ |
| UserController | `if` statements | `when()` method | ✅ |
| RoleController | `if` statements | `when()` method | ✅ |
| PurchaseController | `if` statements | `when()` method | ✅ |
| SaleController | `if` statements | `when()` method | ✅ |
| StockController | `if` statements | `when()` method | ✅ |

**Total: 11 Controllers Updated** 🎉

---

## 🧪 Testing

### Test 1: Filter Produk
```bash
# Test search
GET /api/products?search=kabel

# Test category filter
GET /api/products?category_id=1

# Test status filter
GET /api/products?is_active=1

# Test kombinasi
GET /api/products?search=kabel&category_id=1&is_active=1
```

### Test 2: Filter Purchase
```bash
# Test date range
GET /api/purchases?start_date=2024-01-01&end_date=2024-12-31

# Test supplier filter
GET /api/purchases?supplier_id=1

# Test kombinasi
GET /api/purchases?supplier_id=1&status=received&start_date=2024-01-01
```

### Test 3: Filter Stock
```bash
# Test warehouse filter
GET /api/stocks?warehouse_id=1

# Test low stock
GET /api/stocks?low_stock=1

# Test search product
GET /api/stocks?search=kabel
```

---

## ✅ Hasil

- ✅ Kode lebih clean dan readable
- ✅ Mengikuti Laravel best practice
- ✅ Mengurangi variable mutation
- ✅ Method chaining lebih fluent
- ✅ Automatic null/falsy check
- ✅ Lebih mudah di-maintain

**Semua query sudah direfactor!** 🚀
