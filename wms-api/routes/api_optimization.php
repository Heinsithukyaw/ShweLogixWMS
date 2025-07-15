<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\api\v1\warehouse\WarehouseLayoutController;
use App\Http\Controllers\Admin\api\v1\optimization\OptimizationMetricController;
use App\Http\Controllers\Admin\api\v1\optimization\PredictiveModelController;
use App\Http\Controllers\Admin\api\v1\iot\IoTDeviceController;

/*
|--------------------------------------------------------------------------
| API Routes for Warehouse Optimization
|--------------------------------------------------------------------------
*/

// Warehouse Layout Routes
Route::prefix('warehouse-layouts')->group(function () {
    Route::get('/', [WarehouseLayoutController::class, 'index']);
    Route::post('/', [WarehouseLayoutController::class, 'store']);
    Route::get('/{id}', [WarehouseLayoutController::class, 'show']);
    Route::put('/{id}', [WarehouseLayoutController::class, 'update']);
    Route::delete('/{id}', [WarehouseLayoutController::class, 'destroy']);
    Route::post('/{id}/activate', [WarehouseLayoutController::class, 'activate']);
    Route::post('/{id}/clone', [WarehouseLayoutController::class, 'clone']);
});

// Optimization Metrics Routes
Route::prefix('optimization-metrics')->group(function () {
    Route::get('/', [OptimizationMetricController::class, 'index']);
    Route::post('/', [OptimizationMetricController::class, 'store']);
    Route::get('/{id}', [OptimizationMetricController::class, 'show']);
    Route::put('/{id}', [OptimizationMetricController::class, 'update']);
    Route::delete('/{id}', [OptimizationMetricController::class, 'destroy']);
    Route::get('/summary', [OptimizationMetricController::class, 'summary']);
    Route::get('/compare', [OptimizationMetricController::class, 'compare']);
});

// Predictive Models Routes
Route::prefix('predictive-models')->group(function () {
    Route::get('/', [PredictiveModelController::class, 'index']);
    Route::post('/', [PredictiveModelController::class, 'store']);
    Route::get('/{id}', [PredictiveModelController::class, 'show']);
    Route::put('/{id}', [PredictiveModelController::class, 'update']);
    Route::delete('/{id}', [PredictiveModelController::class, 'destroy']);
    Route::post('/{id}/train', [PredictiveModelController::class, 'train']);
    Route::post('/{id}/predict', [PredictiveModelController::class, 'predict']);
    Route::post('/{id}/activate', [PredictiveModelController::class, 'activate']);
});

// IoT Devices Routes
Route::prefix('iot-devices')->group(function () {
    Route::get('/', [IoTDeviceController::class, 'index']);
    Route::post('/', [IoTDeviceController::class, 'store']);
    Route::get('/{id}', [IoTDeviceController::class, 'show']);
    Route::put('/{id}', [IoTDeviceController::class, 'update']);
    Route::delete('/{id}', [IoTDeviceController::class, 'destroy']);
    Route::get('/{id}/latest-data', [IoTDeviceController::class, 'latestData']);
    Route::get('/{id}/historical-data', [IoTDeviceController::class, 'historicalData']);
    Route::post('/record-data', [IoTDeviceController::class, 'recordData']);
});