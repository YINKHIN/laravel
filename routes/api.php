<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
// Removed KhqrPaymentController import
use App\Http\Controllers\AccountingIntegrationController;
use App\Http\Controllers\EcommerceIntegrationController;
use App\Http\Controllers\NotificationServiceController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduledReportController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SystemStatusController;
use App\Http\Controllers\ThirdPartyIntegrationController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Simple test route
Route::get('/test-api', function () {
    return response()->json(['message' => 'API routes are working']);
});

// Test route for debugging
Route::get('/test-products', function () {
    return response()->json([
        'success' => true,
        'message' => 'Test products endpoint working',
        'data' => [
            ['id' => 1, 'name' => 'Test Product 1'],
            ['id' => 2, 'name' => 'Test Product 2']
        ]
    ]);
});

/*
 * |--------------------------------------------------------------------------
 * | API Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register API routes for your application. These
 * | routes are loaded by the RouteServiceProvider and all of them will
 * | be assigned to the "api" middleware group. Make something great!
 * |
 */

// All API routes are prefixed with /api by default in Laravel
// System status routes (no authentication required for health checks)
Route::prefix('system')->group(function () {
    Route::get('health', [SystemStatusController::class, 'healthCheck']);
    Route::get('metrics', [SystemStatusController::class, 'metrics']);
});

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forgot-password', [AuthController::class, 'requestPasswordReset']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    // auth:sanctum
    Route::middleware('auth:api')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile/{id}', [AuthController::class, 'update']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Read-only routes for all users
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::get('suppliers', [SupplierController::class, 'index']);
    Route::get('suppliers/{id}', [SupplierController::class, 'show']);
    Route::get('staffs', [StaffController::class, 'index']);
    Route::get('staffs/{id}', [StaffController::class, 'show']);
    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/{id}', [CustomerController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::get('brands', [BrandController::class, 'index']);
    Route::get('brands/{id}', [BrandController::class, 'show']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/get-low-stock-products', [ProductController::class, 'getLowStockProducts']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('imports', [ImportController::class, 'index']);
    Route::get('imports/{id}', [ImportController::class, 'show']);
    Route::get('imports/{id}/details', [ImportController::class, 'getDetails']);
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{id}', [PaymentController::class, 'show']);
    
    // Profile image upload
    Route::post('auth/profile/{id}/upload-image', [AuthController::class, 'uploadImage']);

    // Admin-only routes for CUD operations
    Route::middleware('admin')->group(function () {
        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{id}', [UserController::class, 'update']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);

        Route::post('suppliers', [SupplierController::class, 'store']);
        Route::put('suppliers/{id}', [SupplierController::class, 'update']);
        Route::delete('suppliers/{id}', [SupplierController::class, 'destroy']);

        Route::post('staffs', [StaffController::class, 'store']);
        Route::put('staffs/{id}', [StaffController::class, 'update']);
        Route::delete('staffs/{id}', [StaffController::class, 'destroy']);

        Route::post('customers', [CustomerController::class, 'store']);
        Route::put('customers/{id}', [CustomerController::class, 'update']);
        Route::delete('customers/{id}', [CustomerController::class, 'destroy']);

        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{id}', [CategoryController::class, 'update']);
        Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

        Route::post('brands', [BrandController::class, 'store']);
        Route::put('brands/{id}', [BrandController::class, 'update']);
        Route::delete('brands/{id}', [BrandController::class, 'destroy']);

        // Admin-only routes for safety
        Route::delete('products/{id}', [ProductController::class, 'destroy']);
        Route::delete('imports/{id}', [ImportController::class, 'destroy']);

        Route::delete('orders/{id}', [OrderController::class, 'destroy']);
        Route::delete('orders/{id}/force', [OrderController::class, 'forceDestroy']);

        Route::post('payments', [PaymentController::class, 'store']);
        Route::put('payments/{id}', [PaymentController::class, 'update']);
        Route::delete('payments/{id}', [PaymentController::class, 'destroy']);
    });

    // Sales Staff routes for creating orders and payments
    Route::middleware('sales')->group(function () {
        Route::post('orders', [OrderController::class, 'store']);
        Route::put('orders/{id}', [OrderController::class, 'update']);

        Route::post('payments', [PaymentController::class, 'store']);
        Route::put('payments/{id}', [PaymentController::class, 'update']);
        Route::delete('payments/{id}', [PaymentController::class, 'destroy']);
    });

    // Third-party integration routes
    Route::prefix('integrations')->group(function () {
        Route::get('inventory-levels', [ThirdPartyIntegrationController::class, 'getInventoryLevels']);
        Route::get('product-movement-history', [ThirdPartyIntegrationController::class, 'getProductMovementHistory']);
        Route::get('supplier-performance', [ThirdPartyIntegrationController::class, 'getSupplierPerformance']);
        Route::get('customer-purchase-history', [ThirdPartyIntegrationController::class, 'getCustomerPurchaseHistory']);
        Route::get('real-time-sales', [ThirdPartyIntegrationController::class, 'getRealTimeSalesData']);
        Route::get('product-recommendations', [ThirdPartyIntegrationController::class, 'getProductRecommendations']);

        // Webhook endpoint for external systems
        Route::post('webhook', [ThirdPartyIntegrationController::class, 'webhookReceiver']);

        // Payment gateway integrations
        Route::prefix('payments')->group(function () {
            Route::post('paypal/process', [PaymentGatewayController::class, 'processPayPalPayment']);
            Route::post('paypal/capture', [PaymentGatewayController::class, 'capturePayPalPayment']);
            Route::post('stripe/process', [PaymentGatewayController::class, 'processStripePayment']);
            Route::post('stripe/intent', [PaymentGatewayController::class, 'createStripePaymentIntent']);
        });

        // Accounting integrations
        Route::prefix('accounting')->group(function () {
            Route::post('quickbooks/invoice', [AccountingIntegrationController::class, 'createQuickBooksInvoice']);
            Route::post('quickbooks/customer', [AccountingIntegrationController::class, 'createQuickBooksCustomer']);
            Route::post('xero/invoice', [AccountingIntegrationController::class, 'createXeroInvoice']);
            Route::post('xero/contact', [AccountingIntegrationController::class, 'createXeroContact']);
        });

        // E-commerce integrations
        Route::prefix('ecommerce')->group(function () {
            Route::post('shopify/sync-product', [EcommerceIntegrationController::class, 'syncProductToShopify']);
            Route::put('shopify/inventory/{product_id}', [EcommerceIntegrationController::class, 'updateShopifyInventory']);
            Route::post('woocommerce/sync-product', [EcommerceIntegrationController::class, 'syncProductToWooCommerce']);
            Route::put('woocommerce/inventory/{product_id}', [EcommerceIntegrationController::class, 'updateWooCommerceInventory']);
        });

        // Notification service integrations
        Route::prefix('notifications')->group(function () {
            Route::post('sms/send', [NotificationServiceController::class, 'sendSms']);
            Route::post('email/send', [NotificationServiceController::class, 'sendEmail']);
            Route::post('alerts/low-stock', [NotificationServiceController::class, 'sendLowStockAlert']);
            Route::post('alerts/order-confirmation', [NotificationServiceController::class, 'sendOrderConfirmation']);
        });
    });

    // Report routes
    Route::prefix('reports')->middleware('auth:sanctum')->group(function () {
        Route::get('import-report', [ReportController::class, 'getImportReport']);
        Route::get('sales-report', [ReportController::class, 'getSalesReport']);
        Route::get('import-summary', [ReportController::class, 'getImportSummary']);
        Route::get('sales-summary', [ReportController::class, 'getSalesSummary']);
        Route::get('export-import-excel', [ReportController::class, 'exportImportExcel']);
        Route::get('export-sales-excel', [ReportController::class, 'exportSalesExcel']);
        Route::get('export-import-excel-xlsx', [ReportController::class, 'exportImportExcelXlsx']);
        Route::get('export-sales-excel-xlsx', [ReportController::class, 'exportSalesExcelXlsx']);
        Route::get('export-import-pdf', [ReportController::class, 'exportImportPdf']);
        Route::get('export-sales-pdf', [ReportController::class, 'exportSalesPdf']);
        Route::get('export-single-import-word', [ReportController::class, 'exportSingleImportWord']);
        Route::get('export-single-sales-word', [ReportController::class, 'exportSingleSalesWord']);

        // Scheduled reports
        Route::get('best-selling-products', [ScheduledReportController::class, 'bestSellingProducts']);
        Route::get('low-stock-products', [ScheduledReportController::class, 'lowStockProducts']);
        Route::get('inventory-summary', [ScheduledReportController::class, 'inventorySummary']);
    });

    // Additional payment routes
    Route::get('payments/pending', [PaymentController::class, 'getPendingPayments']);
    Route::get('payments/summary', [PaymentController::class, 'getPaymentSummary']);
    Route::get('payments/order/{orderId}/status', [PaymentController::class, 'getOrderPaymentStatus']);
    Route::post('payments/cleanup', [PaymentController::class, 'cleanupPaymentData']);

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::put('/{id}', [NotificationController::class, 'update']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    });

    // Additional product routes (must be before products/{id} route)
    Route::get('products/low-stock', [ProductController::class, 'getLowStockProducts']);
    Route::get('products/expired', [ProductController::class, 'getExpiredProducts']);
    Route::get('products/near-expiration', [ProductController::class, 'getNearExpirationProducts']);
    
    // Product and Import management routes (admin OR inventory staff)
    Route::middleware(['admin_or_inventory'])->group(function () {
        // Product management
        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{id}', [ProductController::class, 'update']);
        Route::post('products/{id}/deactivate', [ProductController::class, 'deactivate']);
        Route::post('products/{id}/activate', [ProductController::class, 'activate']);
        
        // Import management
        Route::post('imports', [ImportController::class, 'store']);
        Route::put('imports/{id}', [ImportController::class, 'update']);
    });
});
