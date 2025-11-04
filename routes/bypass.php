<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportController;

// BYPASS ROUTES - NO AUTHENTICATION REQUIRED
// These routes bypass all middleware for demo purposes

Route::prefix('bypass')->group(function () {
    
    // Products
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);
    Route::post('products/{id}/activate', [ProductController::class, 'activate']);
    Route::post('products/{id}/deactivate', [ProductController::class, 'deactivate']);
    
    // Orders
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::put('orders/{id}', [OrderController::class, 'update']);
    Route::delete('orders/{id}', [OrderController::class, 'destroy']);
    
    // Payments
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{id}', [PaymentController::class, 'show']);
    Route::post('payments', [PaymentController::class, 'store']);
    Route::put('payments/{id}', [PaymentController::class, 'update']);
    Route::delete('payments/{id}', [PaymentController::class, 'destroy']);
    
    // Customers
    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/{id}', [CustomerController::class, 'show']);
    Route::post('customers', [CustomerController::class, 'store']);
    Route::put('customers/{id}', [CustomerController::class, 'update']);
    Route::delete('customers/{id}', [CustomerController::class, 'destroy']);
    
    // Suppliers
    Route::get('suppliers', [SupplierController::class, 'index']);
    Route::get('suppliers/{id}', [SupplierController::class, 'show']);
    Route::post('suppliers', [SupplierController::class, 'store']);
    Route::put('suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('suppliers/{id}', [SupplierController::class, 'destroy']);
    
    // Categories
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
    
    // Brands
    Route::get('brands', [BrandController::class, 'index']);
    Route::get('brands/{id}', [BrandController::class, 'show']);
    Route::post('brands', [BrandController::class, 'store']);
    Route::put('brands/{id}', [BrandController::class, 'update']);
    Route::delete('brands/{id}', [BrandController::class, 'destroy']);
    
    // Staff
    Route::get('staff', [StaffController::class, 'index']);
    Route::get('staff/{id}', [StaffController::class, 'show']);
    Route::post('staff', [StaffController::class, 'store']);
    Route::put('staff/{id}', [StaffController::class, 'update']);
    Route::delete('staff/{id}', [StaffController::class, 'destroy']);
    
    // Users
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
    
    // Imports
    Route::get('imports', [ImportController::class, 'index']);
    Route::get('imports/{id}', [ImportController::class, 'show']);
    Route::post('imports', [ImportController::class, 'store']);
    Route::put('imports/{id}', [ImportController::class, 'update']);
    Route::delete('imports/{id}', [ImportController::class, 'destroy']);
    
    // Reports
    Route::get('reports/import', [ReportController::class, 'getImportReports']);
    Route::get('reports/sales', [ReportController::class, 'getSalesReports']);
    Route::get('reports/inventory', [ReportController::class, 'getInventoryReports']);
    Route::get('reports/financial', [ReportController::class, 'getFinancialReports']);
    
    // Test endpoint
    Route::get('test', function() {
        return response()->json([
            'message' => 'Bypass routes working!',
            'timestamp' => now(),
            'status' => 'success'
        ]);
    });
});