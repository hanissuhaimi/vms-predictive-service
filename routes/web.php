<?php

use App\Http\Controllers\PredictionController;
use App\Http\Controllers\VehicleHistoryController;
use App\Http\Controllers\MaintenanceAnalyticsController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\VehicleSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Homepage redirect
Route::get('/', function () {
    return redirect()->route('prediction.index');
});

// ========================================
// PREDICTION ROUTES
// ========================================

Route::prefix('prediction')->name('prediction.')->group(function () {
    Route::get('/', [PredictionController::class, 'index'])->name('index');
    Route::post('/predict', [PredictionController::class, 'predict'])->name('predict');
    Route::get('/result/{vehicle}/{mileage}', [PredictionController::class, 'show'])
         ->name('show')
         ->where(['vehicle' => '[A-Za-z0-9]+', 'mileage' => '[0-9]+']);
});

// ========================================
// MAINTENANCE HISTORY ROUTES
// ========================================

Route::prefix('maintenance')->name('maintenance.')->group(function () {
    Route::get('/history/{vehicle}', [VehicleHistoryController::class, 'show'])
         ->name('history')
         ->where('vehicle', '[A-Za-z0-9]+');
    
    Route::get('/history/{vehicle}/export', [VehicleHistoryController::class, 'export'])
         ->name('history.export')
         ->where('vehicle', '[A-Za-z0-9]+');
});

// ========================================
// ANALYTICS ROUTES
// ========================================

Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/dashboard', [MaintenanceAnalyticsController::class, 'dashboard'])
         ->name('dashboard');
});

// ========================================
// DEPOT MANAGEMENT ROUTES
// ========================================

Route::prefix('depots')->name('depots.')->group(function () {
    Route::get('/', [DepotController::class, 'index'])->name('index');
    Route::get('/{depot}', [DepotController::class, 'show'])
         ->name('show')
         ->where('depot', '[0-9]+');
});

// ========================================
// VEHICLE SEARCH ROUTES
// ========================================

Route::prefix('vehicles')->name('vehicles.')->group(function () {
    Route::get('/search', [VehicleSearchController::class, 'search'])->name('search');
    Route::get('/maintenance-needed', [VehicleSearchController::class, 'needingMaintenance'])
         ->name('maintenance-needed');
});

// ========================================
// ANALYTICS ROUTES
// ========================================

Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/dashboard', [MaintenanceAnalyticsController::class, 'dashboard'])
         ->name('dashboard');
});

Route::get('/analytics/test', function() {
    return response()->json([
        'status' => 'success',
        'message' => 'Analytics system is working',
        'timestamp' => now()->format('Y-m-d H:i:s'),
        'test_data' => [
            'total_vehicles' => 100,
            'total_services' => 88000,
            'processing_time' => '0.5 seconds'
        ]
    ]);
})->name('analytics.test');