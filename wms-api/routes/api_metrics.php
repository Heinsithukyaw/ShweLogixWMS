<?php

use App\Http\Controllers\Api\Metrics\DashboardController;
use App\Http\Controllers\Api\Metrics\DashboardWidgetController;
use App\Http\Controllers\Api\Metrics\DataCollectionPointController;
use App\Http\Controllers\Api\Metrics\MetricDataController;
use App\Http\Controllers\Api\Metrics\MetricDefinitionController;
use App\Http\Controllers\Api\Metrics\MetricVisualizationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Metrics API Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'metrics'], function () {
    // Metric Definition and Data Collection Routes
    Route::apiResource('metric-definitions', MetricDefinitionController::class);
    Route::apiResource('data-collection-points', DataCollectionPointController::class);
    Route::apiResource('metric-data', MetricDataController::class);

    // Visualization and Dashboard Routes
    Route::apiResource('metric-visualizations', MetricVisualizationController::class);
    Route::apiResource('dashboards', DashboardController::class);
    Route::apiResource('dashboard-widgets', DashboardWidgetController::class);
});