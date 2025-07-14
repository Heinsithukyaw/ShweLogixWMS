<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SpaceUtilization\WarehouseZoneController;
use App\Http\Controllers\Api\SpaceUtilization\WarehouseAisleController;
use App\Http\Controllers\Api\SpaceUtilization\SpaceUtilizationController;
use App\Http\Controllers\Api\SpaceUtilization\HeatMapController;
use App\Http\Controllers\Api\Visualization\WarehouseFloorPlanController;
use App\Http\Controllers\Api\Visualization\WarehouseEquipmentController;
use App\Http\Controllers\Api\Reporting\ReportTemplateController;
use App\Http\Controllers\Api\Reporting\CustomReportController;
use App\Http\Controllers\Api\Dashboard\WidgetLibraryController;

/*
|--------------------------------------------------------------------------
| Phase 2 API Routes - Visualization & Reporting
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    // Space Utilization Analytics
    Route::prefix('space-utilization')->group(function () {
        // Warehouse Zones
        Route::apiResource('zones', WarehouseZoneController::class);
        Route::get('zones/{zone}/utilization', [WarehouseZoneController::class, 'utilization']);
        Route::get('zones/{zone}/capacity', [WarehouseZoneController::class, 'capacity']);
        Route::get('zones/{zone}/analytics', [WarehouseZoneController::class, 'analytics']);
        Route::get('zones/{zone}/heat-map', [WarehouseZoneController::class, 'heatMap']);
        Route::post('zones/{zone}/snapshot', [WarehouseZoneController::class, 'createSnapshot']);
        
        // Warehouse Aisles
        Route::apiResource('aisles', WarehouseAisleController::class);
        Route::get('aisles/{aisle}/utilization', [WarehouseAisleController::class, 'utilization']);
        Route::get('aisles/{aisle}/efficiency', [WarehouseAisleController::class, 'efficiency']);
        Route::put('aisles/{aisle}/locations', [WarehouseAisleController::class, 'updateLocations']);
        Route::get('zones/{zone}/aisles', [WarehouseAisleController::class, 'byZone']);
        Route::get('aisles/performance-comparison', [WarehouseAisleController::class, 'performanceComparison']);
        
        // Space Utilization Analytics
        Route::get('overview', [SpaceUtilizationController::class, 'overview']);
        Route::get('snapshots', [SpaceUtilizationController::class, 'snapshots']);
        Route::post('snapshots', [SpaceUtilizationController::class, 'createSnapshot']);
        Route::get('zones/{zone}/analytics', [SpaceUtilizationController::class, 'zoneAnalytics']);
        Route::get('comparison', [SpaceUtilizationController::class, 'zoneComparison']);
        Route::get('dashboard', [SpaceUtilizationController::class, 'dashboard']);
        Route::post('report', [SpaceUtilizationController::class, 'report']);
        
        // Heat Maps
        Route::get('heat-maps', [HeatMapController::class, 'index']);
        Route::post('heat-maps', [HeatMapController::class, 'store']);
        Route::get('zones/{zone}/heat-map', [HeatMapController::class, 'zoneHeatMap']);
        Route::get('heat-maps/analytics', [HeatMapController::class, 'analytics']);
        Route::get('heat-maps/real-time', [HeatMapController::class, 'realTimeOverlay']);
        Route::post('heat-maps/report', [HeatMapController::class, 'report']);
    });
    
    // 2D Warehouse Visualization
    Route::prefix('visualization')->group(function () {
        // Floor Plans
        Route::apiResource('floor-plans', WarehouseFloorPlanController::class);
        Route::get('floor-plans/active/current', [WarehouseFloorPlanController::class, 'active']);
        Route::put('floor-plans/{floorPlan}/activate', [WarehouseFloorPlanController::class, 'setActive']);
        Route::get('floor-plans/{floorPlan}/with-equipment', [WarehouseFloorPlanController::class, 'withEquipment']);
        Route::get('floor-plans/{floorPlan}/with-utilization', [WarehouseFloorPlanController::class, 'withUtilization']);
        Route::get('floor-plans/{floorPlan}/with-heat-map', [WarehouseFloorPlanController::class, 'withHeatMap']);
        Route::put('floor-plans/{floorPlan}/layout', [WarehouseFloorPlanController::class, 'updateLayout']);
        Route::get('floor-plans/{floorPlan}/config', [WarehouseFloorPlanController::class, 'visualizationConfig']);
        Route::post('floor-plans/{floorPlan}/export', [WarehouseFloorPlanController::class, 'export']);
        
        // Equipment Tracking
        Route::apiResource('equipment', WarehouseEquipmentController::class);
        Route::put('equipment/{equipment}/position', [WarehouseEquipmentController::class, 'updatePosition']);
        Route::get('equipment/{equipment}/movements', [WarehouseEquipmentController::class, 'movements']);
        Route::get('equipment/{equipment}/analytics', [WarehouseEquipmentController::class, 'analytics']);
        Route::get('equipment/real-time/status', [WarehouseEquipmentController::class, 'realTimeStatus']);
        Route::get('zones/{zone}/equipment', [WarehouseEquipmentController::class, 'byZone']);
        Route::get('equipment/{equipment}/track-path', [WarehouseEquipmentController::class, 'trackPath']);
    });
    
    // Advanced Reporting Engine
    Route::prefix('reporting')->group(function () {
        // Report Templates
        Route::apiResource('templates', ReportTemplateController::class);
        Route::get('data-sources', [ReportTemplateController::class, 'dataSources']);
        Route::get('field-types', [ReportTemplateController::class, 'fieldTypes']);
        Route::get('filter-types', [ReportTemplateController::class, 'filterTypes']);
        Route::get('templates/{template}/preview', [ReportTemplateController::class, 'preview']);
        Route::post('templates/validate', [ReportTemplateController::class, 'validateTemplate']);
        Route::get('categories', [ReportTemplateController::class, 'categories']);
        Route::post('templates/{template}/clone', [ReportTemplateController::class, 'clone']);
        
        // Custom Reports
        Route::apiResource('reports', CustomReportController::class);
        Route::post('generate', [CustomReportController::class, 'generate']);
        Route::post('reports/{report}/regenerate', [CustomReportController::class, 'regenerate']);
        Route::get('reports/{report}/download', [CustomReportController::class, 'download']);
        Route::get('statistics', [CustomReportController::class, 'statistics']);
        Route::get('scheduled', [CustomReportController::class, 'scheduled']);
        Route::post('execute-scheduled', [CustomReportController::class, 'executeScheduled']);
    });
    
    // Dashboard Enhancements
    Route::prefix('dashboards')->group(function () {
        // Widget Library
        Route::apiResource('widgets', WidgetLibraryController::class);
        Route::get('widget-categories', [WidgetLibraryController::class, 'categories']);
        Route::get('widget-types', [WidgetLibraryController::class, 'widgetTypes']);
        Route::get('widget-data-sources', [WidgetLibraryController::class, 'dataSources']);
        Route::post('widget-config-template', [WidgetLibraryController::class, 'configTemplate']);
        Route::post('validate-config', [WidgetLibraryController::class, 'validateConfig']);
        Route::get('widgets/{widget}/preview', [WidgetLibraryController::class, 'preview']);
        Route::post('widgets/{widget}/clone', [WidgetLibraryController::class, 'clone']);
    });
    
    // Cross-feature Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('warehouse-overview', function() {
            return response()->json([
                'space_utilization' => 'Space utilization summary',
                'equipment_status' => 'Equipment status summary',
                'performance_metrics' => 'Performance metrics summary'
            ]);
        });
        
        Route::get('performance-insights', function() {
            return response()->json([
                'efficiency_trends' => 'Efficiency trends',
                'bottlenecks' => 'Identified bottlenecks',
                'recommendations' => 'Improvement recommendations'
            ]);
        });
        
        Route::get('predictive-analytics', function() {
            return response()->json([
                'capacity_forecast' => 'Capacity forecasting',
                'maintenance_predictions' => 'Equipment maintenance predictions',
                'demand_patterns' => 'Demand pattern analysis'
            ]);
        });
    });
});