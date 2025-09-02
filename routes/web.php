<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MaintenanceAnalyticsController;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

// Main application routes
Route::get('/', [PredictionController::class, 'index'])->name('prediction.index');
Route::post('/predict', [PredictionController::class, 'predict'])->name('prediction.predict');
Route::get('/prediction/{vehicle}/{mileage}', [PredictionController::class, 'showPrediction'])->name('prediction.show');
Route::post('/quick-save', [PredictionController::class, 'quickSave'])->name('prediction.quickSave');

// Maintenance history routes
Route::get('/maintenance-history/{vehicle}/{mileage?}', [PredictionController::class, 'maintenanceHistory'])->name('maintenance.history');
Route::get('/maintenance-history/{vehicle}/export', [PredictionController::class, 'exportHistory'])->name('maintenance.history.export');

// Service Request routes (updated for existing table)
Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
Route::get('/maintenance/create', [MaintenanceController::class, 'create'])->name('maintenance.create');
Route::post('/maintenance/store', [MaintenanceController::class, 'store'])->name('maintenance.store');
Route::get('/maintenance/{id}', [MaintenanceController::class, 'show'])->name('maintenance.show');

//Route::prefix('fleet')->name('fleet.')->group(function () {
    //Route::get('/analysis', [FleetAnalysisController::class, 'index'])->name('analysis.index');
    //Route::get('/analysis/all', [FleetAnalysisController::class, 'analyzeAll'])->name('analysis.all');
    
    // Future fleet analysis routes (placeholder)
    //Route::get('/analysis/performance', [FleetAnalysisController::class, 'performance'])->name('analysis.performance');
    //Route::get('/analysis/costs', [FleetAnalysisController::class, 'costs'])->name('analysis.costs');
    //Route::get('/analysis/maintenance-trends', [FleetAnalysisController::class, 'maintenanceTrends'])->name('analysis.maintenance-trends');
    //Route::get('/analysis/export', [FleetAnalysisController::class, 'export'])->name('analysis.export');
//});

// API route for vehicle history
Route::get('/api/vehicle-history/{vehicle}', [PredictionController::class, 'getVehicleHistory'])->name('api.vehicle.history');

Route::get('/prediction', [PredictionController::class, 'index'])->name('prediction.index');
Route::post('/prediction/predict', [PredictionController::class, 'predict'])->name('prediction.predict');

// API route for Maintenance Analytics
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/dashboard', [MaintenanceAnalyticsController::class, 'dashboard'])
         ->name('dashboard');
});