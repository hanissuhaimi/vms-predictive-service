<?php

use App\Http\Controllers\PredictionController;
use App\Http\Controllers\VehicleHistoryController;
use App\Http\Controllers\MaintenanceAnalyticsController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\VehicleSearchController;
use Illuminate\Support\Facades\Route;

// ========================================
// API ROUTES (routes/api.php)
// ========================================

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.')->group(function () {
    
    // Prediction API
    Route::prefix('prediction')->name('prediction.')->group(function () {
        Route::post('/', [PredictionController::class, 'apiPredict'])->name('predict');
    });
    
    // Analytics API
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/trends', [MaintenanceAnalyticsController::class, 'apiServiceTrends'])
             ->name('trends');
    });
    
    // Vehicle API
    Route::prefix('vehicles')->name('vehicles.')->group(function () {
        Route::get('/search', [VehicleSearchController::class, 'search'])->name('search');
    });
});