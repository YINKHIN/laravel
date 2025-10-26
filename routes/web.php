<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SystemStatusController;

Route::get('/', function () {
    return view('welcome');
});

// Add login route to fix "Route [login] not defined" error
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthorized'], 401);
})->name('login');

// System status routes (no authentication required for health checks)
Route::prefix('api/system')->group(function () {
    Route::get('health', [SystemStatusController::class, 'healthCheck']);
    Route::get('metrics', [SystemStatusController::class, 'metrics']);
});

// Removed catch-all route completely