<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\RackController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleReturnController;
use App\Http\Controllers\Api\SaleHoldController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\ReportController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

    // User management
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);

    // Role & Permission management
    Route::apiResource('roles', RoleController::class);
    Route::get('permissions', [RoleController::class, 'permissions']);

    // Master data
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('units', UnitController::class);
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('racks', RackController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('customers', CustomerController::class);

    // Products
    Route::apiResource('products', ProductController::class);
    Route::get('products/barcode/search', [ProductController::class, 'searchBarcode']);

    // Purchases
    Route::apiResource('purchases', PurchaseController::class)->only(['index', 'store', 'show']);
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel']);

    // Sales
    Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show']);
    Route::post('sales/{sale}/payment', [SaleController::class, 'addPayment']);
    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel']);
    Route::post('sales/{sale}/print-receipt', [SaleController::class, 'printReceipt']);

    Route::get('sale-returns', [SaleReturnController::class, 'index']);
    Route::post('sale-returns', [SaleReturnController::class, 'store']);
    Route::get('sale-returns/{sale_return}', [SaleReturnController::class, 'show']);

    Route::get('sale-holds', [SaleHoldController::class, 'index']);
    Route::post('sale-holds', [SaleHoldController::class, 'store']);
    Route::get('sale-holds/{sale_hold}', [SaleHoldController::class, 'show']);
    Route::delete('sale-holds/{sale_hold}', [SaleHoldController::class, 'destroy']);

    // Stock management
    Route::get('stocks', [StockController::class, 'index']);
    Route::post('stocks/adjustment', [StockController::class, 'adjustment']);
    Route::get('stocks/movements', [StockController::class, 'movements']);

    // Store (Identitas Toko)
    Route::get('store', [StoreController::class, 'show']);
    Route::put('store', [StoreController::class, 'update']);

    // Reports
    Route::get('reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('reports/sales', [ReportController::class, 'salesReport']);
    Route::get('reports/purchases', [ReportController::class, 'purchasesReport']);
    Route::get('reports/stock', [ReportController::class, 'stockReport']);
    Route::get('reports/profit', [ReportController::class, 'profitReport']);
    Route::get('reports/supplier-customer', [ReportController::class, 'supplierCustomerReport']);
    Route::get('reports/sell-payment', [ReportController::class, 'sellPaymentReport']);
    Route::get('reports/stock-movements', [ReportController::class, 'stockMovementReport']);
    Route::get('reports/daily-cashier-summary', [ReportController::class, 'dailyCashierSummary']);

    // Export reports
    Route::get('reports/export/sales', [ReportController::class, 'exportSales']);
    Route::get('reports/export/purchases', [ReportController::class, 'exportPurchases']);
    Route::get('reports/export/stock', [ReportController::class, 'exportStock']);
});
