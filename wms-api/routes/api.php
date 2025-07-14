<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Include advanced feature routes
require __DIR__ . '/api-advanced.php';
require __DIR__ . '/api-olap.php';

// Include Financial and Metrics routes
require __DIR__ . '/api_financial.php';
require __DIR__ . '/api_metrics.php';

// Warehouse Optimization Routes
Route::group(['prefix' => 'warehouse-optimization', 'namespace' => 'App\Http\Controllers\Optimization'], function () {
    // Optimization Metrics
    Route::get('metrics', 'OptimizationMetricController@index');
    Route::post('metrics', 'OptimizationMetricController@store');
    Route::get('metrics/{id}', 'OptimizationMetricController@show');
    Route::put('metrics/{id}', 'OptimizationMetricController@update');
    Route::delete('metrics/{id}', 'OptimizationMetricController@destroy');
    Route::get('metrics/summary', 'OptimizationMetricController@getSummary');
    Route::get('metrics/compare', 'OptimizationMetricController@compareMetrics');
    
    // Predictive Models
    Route::get('models', 'PredictiveModelController@index');
    Route::post('models', 'PredictiveModelController@store');
    Route::get('models/{id}', 'PredictiveModelController@show');
    Route::put('models/{id}', 'PredictiveModelController@update');
    Route::delete('models/{id}', 'PredictiveModelController@destroy');
    Route::post('models/{id}/train', 'PredictiveModelController@trainModel');
    Route::post('models/{id}/predict', 'PredictiveModelController@predict');
    Route::get('models/{id}/performance', 'PredictiveModelController@getPerformance');
    
    // IoT Devices
    Route::get('iot-devices', 'IoTDeviceController@index');
    Route::post('iot-devices', 'IoTDeviceController@store');
    Route::get('iot-devices/{id}', 'IoTDeviceController@show');
    Route::put('iot-devices/{id}', 'IoTDeviceController@update');
    Route::delete('iot-devices/{id}', 'IoTDeviceController@destroy');
    Route::post('iot-devices/{id}/record-data', 'IoTDeviceController@recordData');
    Route::get('iot-devices/{id}/data', 'IoTDeviceController@getData');
    Route::get('iot-devices/{id}/status', 'IoTDeviceController@getStatus');
});