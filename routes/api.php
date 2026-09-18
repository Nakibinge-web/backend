<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StockMovementController;

use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\InvoiceController;

// Public auth routes — no token required
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// All other routes require a valid Sanctum token + tenant resolution
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Test
    Route::get('/test', function () {
        return response()->json(['success' => true, 'message' => 'API is working!', 'timestamp' => now()]);
    });

    // Health Check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Inventory & Sales Management API',
            'version' => '1.0.0',
            'status' => 'healthy',
            'timestamp' => now(),
            'endpoints' => [
                'tenants'         => '/api/tenants',
                'users'           => '/api/users',
                'categories'      => '/api/categories',
                'suppliers'       => '/api/suppliers',
                'products'        => '/api/products',
                'sales'           => '/api/sales',
                'purchases'       => '/api/purchases',
                'stock-movements' => '/api/stock-movements',
            ],
        ]);
    });

    // Roles
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/{role}', [RoleController::class, 'show']);

        // Only owners can create, modify, or assign custom roles
        Route::middleware('role:owner')->group(function () {
            Route::post('/', [RoleController::class, 'store']);
            Route::post('/assign', [RoleController::class, 'assign']);
            Route::post('/with-permissions', [RoleController::class, 'storeWithPermissions']);
            Route::delete('/revoke', [RoleController::class, 'revoke']);
            Route::put('/{role}', [RoleController::class, 'update']);
            Route::delete('/{role}', [RoleController::class, 'destroy']);
        });
    });

    // Permissions
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::post('/assign', [PermissionController::class, 'assign']);
        Route::delete('/revoke', [PermissionController::class, 'revoke']);
        Route::get('/{permission}', [PermissionController::class, 'show']);
        Route::put('/{permission}', [PermissionController::class, 'update']);
        Route::delete('/{permission}', [PermissionController::class, 'destroy']);
    });

    // Tenants
    Route::prefix('tenants')->group(function () {
        Route::get('/', [TenantController::class, 'index']);
        Route::post('/', [TenantController::class, 'store']);
        Route::get('/{tenant}', [TenantController::class, 'show']);
        Route::put('/{tenant}', [TenantController::class, 'update']);
        Route::delete('/{tenant}', [TenantController::class, 'destroy']);
    });

    // Users
    Route::prefix('users')->group(function () {
        Route::get('/',         [UserController::class, 'index']);
        Route::get('/me',       [UserController::class, 'me']);
        Route::get('/by-role',  [UserController::class, 'getUsersByRole']);
        Route::get('/{user}',   [UserController::class, 'show']);

        // Only owners and admins can create, update, or delete users,
        // OR users who have the relevant granular permission.
        // Permission checks are handled inside the controller.
        Route::post('/',          [UserController::class, 'store']);
        Route::put('/{user}',     [UserController::class, 'update']);
        Route::delete('/{user}',  [UserController::class, 'destroy']);

        // Only owners can create users with custom roles or modify custom role permissions
        Route::middleware('role:owner')->group(function () {
            Route::post('/with-custom-role',                    [UserController::class, 'storeWithCustomRole']);
            Route::put('/{user}/custom-role-permissions',       [UserController::class, 'updateCustomRolePermissions']);
        });
    });

    // Categories
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{category}', [CategoryController::class, 'show']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
    });

    // Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::post('/', [SupplierController::class, 'store']);
        Route::get('/{supplier}', [SupplierController::class, 'show']);
        Route::put('/{supplier}', [SupplierController::class, 'update']);
        Route::delete('/{supplier}', [SupplierController::class, 'destroy']);
    });

    // Customers
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/{customer}', [CustomerController::class, 'show']);
        Route::put('/{customer}', [CustomerController::class, 'update']);
        Route::delete('/{customer}', [CustomerController::class, 'destroy']);
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::get('/generate-sku', [ProductController::class, 'generateSku']);
        Route::get('/low-stock',    [ProductController::class, 'getLowStock']);
        Route::get('/',             [ProductController::class, 'index']);
        Route::post('/',            [ProductController::class, 'store']);
        Route::post('/bulk',        [ProductController::class, 'bulkStore']);
        Route::get('/{product}',    [ProductController::class, 'show']);
        Route::put('/{product}',    [ProductController::class, 'update']);
        Route::delete('/{product}', [ProductController::class, 'destroy']);
    });

    // Sales
    // No route-level role middleware — the SaleController handles all permission
    // checks itself, supporting both built-in roles and custom permission grants
    // (sales.create, sales.view, sales.report).
    Route::prefix('sales')->group(function () {
        // Report routes must be declared before /{sale} wildcard
        Route::get('/daily-report',   [SaleController::class, 'getDailyReport']);
        Route::get('/weekly-report',  [SaleController::class, 'getWeeklyReport']);
        Route::get('/monthly-report', [SaleController::class, 'getMonthlySalesReport']);
        Route::get('/yearly-report',  [SaleController::class, 'getYearlyReport']);

        Route::get('/',        [SaleController::class, 'index']);
        Route::post('/',       [SaleController::class, 'store']);
        Route::get('/{sale}',  [SaleController::class, 'show']);
        Route::put('/{sale}',  [SaleController::class, 'update']);
        Route::delete('/{sale}', [SaleController::class, 'destroy']);
    });

    // Purchases
    Route::prefix('purchases')->group(function () {
        Route::get('/', [PurchaseController::class, 'index']);
        Route::post('/', [PurchaseController::class, 'store']);
        Route::get('/monthly-report', [PurchaseController::class, 'getMonthlyReport']);
        Route::get('/daily-report',   [PurchaseController::class, 'getDailyReport']);
        Route::get('/{purchase}', [PurchaseController::class, 'show']);
        Route::put('/{purchase}', [PurchaseController::class, 'update']);
        Route::delete('/{purchase}', [PurchaseController::class, 'destroy']);
    });

    // Invoices (custom / proforma invoices stored in DB)
    Route::prefix('invoices')->group(function () {
        Route::get('/',             [InvoiceController::class, 'index']);
        Route::post('/',            [InvoiceController::class, 'store']);
        Route::get('/{invoice}',    [InvoiceController::class, 'show']);
        Route::put('/{invoice}',    [InvoiceController::class, 'update']);
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy']);
    });

    // AI Assistant
    Route::post('/ai/chat', [\App\Http\Controllers\AiController::class, 'chat']);

    // Stock Movements
    Route::prefix('stock-movements')->group(function () {
        Route::get('/', [StockMovementController::class, 'index']);
        Route::post('/', [StockMovementController::class, 'store']);
        Route::get('/product/{productId}', [StockMovementController::class, 'getByProduct']);
        Route::get('/by-type', [StockMovementController::class, 'getByType']);
        Route::get('/date-range', [StockMovementController::class, 'getByDateRange']);
        Route::get('/{stockMovement}', [StockMovementController::class, 'show']);
    });
});
