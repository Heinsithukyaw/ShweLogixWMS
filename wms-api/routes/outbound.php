<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Outbound\OrderAllocationController;
use App\Http\Controllers\Outbound\OrderPriorityController;
use App\Http\Controllers\Outbound\BackOrderController;
use App\Http\Controllers\Outbound\OrderConsolidationController;
use App\Http\Controllers\Outbound\PickListController;
use App\Http\Controllers\Outbound\BatchPickController;
use App\Http\Controllers\Outbound\ZonePickController;
use App\Http\Controllers\Outbound\ClusterPickController;
use App\Http\Controllers\Outbound\PackingController;
use App\Http\Controllers\Outbound\PackingStationController;
use App\Http\Controllers\Outbound\PackOrderController;
use App\Http\Controllers\Outbound\CartonTypeController;
use App\Http\Controllers\Outbound\ShippingController;
use App\Http\Controllers\Outbound\ShipmentController;
use App\Http\Controllers\Outbound\ShippingRateController;
use App\Http\Controllers\Outbound\LoadPlanningController;
use App\Http\Controllers\Outbound\LoadPlanController;
use App\Http\Controllers\Outbound\DockSchedulingController;
use App\Http\Controllers\Outbound\DockScheduleController;
use App\Http\Controllers\Outbound\QualityControlController;

// Additional Outbound Controllers
use App\Http\Controllers\Outbound\PackingController;
use App\Http\Controllers\Outbound\ShippingController;
use App\Http\Controllers\Outbound\LoadPlanningController;
use App\Http\Controllers\Outbound\DockSchedulingController;

/*
|--------------------------------------------------------------------------
| Outbound Operations API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('outbound')->group(function () {
    
    // Order Management & Allocation
    Route::prefix('orders')->group(function () {
        // Order Allocations
        Route::get('allocations', [OrderAllocationController::class, 'index']);
        Route::post('allocations/item', [OrderAllocationController::class, 'allocateItem']);
        Route::get('allocations/{id}', [OrderAllocationController::class, 'show']);
        Route::put('allocations/{id}', [OrderAllocationController::class, 'update']);
        Route::delete('allocations/{id}/cancel', [OrderAllocationController::class, 'cancel']);
        Route::post('allocations/reallocate-expired', [OrderAllocationController::class, 'reallocateExpired']);
        Route::post('allocations/bulk-allocate', [OrderAllocationController::class, 'bulkAllocate']);
        Route::get('allocations/inventory/available', [OrderAllocationController::class, 'availableInventory']);
        
        Route::post('{orderId}/allocate', [OrderAllocationController::class, 'allocateOrder']);
        Route::get('{orderId}/allocation-summary', [OrderAllocationController::class, 'orderSummary']);
        
        // Order Priorities
        Route::get('priorities', [OrderPriorityController::class, 'index']);
        Route::post('priorities/calculate', [OrderPriorityController::class, 'calculatePriorities']);
        Route::post('{orderId}/priority', [OrderPriorityController::class, 'setPriority']);
        Route::put('priorities/{id}', [OrderPriorityController::class, 'update']);
        Route::get('priorities/high-priority', [OrderPriorityController::class, 'getHighPriority']);
        
        // Back Orders
        Route::get('backorders', [BackOrderController::class, 'index']);
        Route::post('backorders', [BackOrderController::class, 'store']);
        Route::get('backorders/{id}', [BackOrderController::class, 'show']);
        Route::put('backorders/{id}', [BackOrderController::class, 'update']);
        Route::post('backorders/{id}/fulfill', [BackOrderController::class, 'fulfill']);
        Route::post('backorders/auto-fulfill', [BackOrderController::class, 'autoFulfill']);
        Route::get('backorders/overdue', [BackOrderController::class, 'getOverdue']);
        
        // Order Consolidations
        Route::get('consolidations', [OrderConsolidationController::class, 'index']);
        Route::post('consolidations', [OrderConsolidationController::class, 'store']);
        Route::get('consolidations/{id}', [OrderConsolidationController::class, 'show']);
        Route::put('consolidations/{id}', [OrderConsolidationController::class, 'update']);
        Route::post('consolidations/{id}/process', [OrderConsolidationController::class, 'process']);
        Route::delete('consolidations/{id}', [OrderConsolidationController::class, 'destroy']);
        Route::post('consolidations/auto-consolidate', [OrderConsolidationController::class, 'autoConsolidate']);
    });
    
    // Pick Management
    Route::prefix('picking')->group(function () {
        // Pick Lists
        Route::get('lists', [PickListController::class, 'index']);
        Route::post('waves/{waveId}/generate-lists', [PickListController::class, 'generateForWave']);
        Route::get('lists/{id}', [PickListController::class, 'show']);
        Route::post('lists/{id}/assign', [PickListController::class, 'assign']);
        Route::post('lists/{id}/start', [PickListController::class, 'start']);
        Route::post('lists/{id}/complete', [PickListController::class, 'complete']);
        Route::post('lists/{id}/items/{itemId}/pick', [PickListController::class, 'pickItem']);
        Route::post('lists/{id}/items/{itemId}/exception', [PickListController::class, 'createException']);
        Route::post('lists/{id}/optimize-sequence', [PickListController::class, 'optimizeSequence']);
        Route::get('lists/{id}/performance', [PickListController::class, 'performance']);
        Route::post('lists/bulk-assign', [PickListController::class, 'bulkAssign']);
        Route::get('lists/summary', [PickListController::class, 'summary']);
        
        // Batch Picking
        Route::get('batches', [BatchPickController::class, 'index']);
        Route::post('batches', [BatchPickController::class, 'store']);
        Route::get('batches/{id}', [BatchPickController::class, 'show']);
        Route::post('batches/{id}/assign', [BatchPickController::class, 'assign']);
        Route::post('batches/{id}/start', [BatchPickController::class, 'start']);
        Route::post('batches/{id}/complete', [BatchPickController::class, 'complete']);
        
        // Zone Picking
        Route::get('zones', [ZonePickController::class, 'index']);
        Route::post('zones', [ZonePickController::class, 'store']);
        Route::get('zones/{id}', [ZonePickController::class, 'show']);
        Route::post('zones/{id}/assign', [ZonePickController::class, 'assign']);
        Route::post('zones/{id}/start', [ZonePickController::class, 'start']);
        Route::post('zones/{id}/complete', [ZonePickController::class, 'complete']);
        
        // Cluster Picking
        Route::get('clusters', [ClusterPickController::class, 'index']);
        Route::post('clusters', [ClusterPickController::class, 'store']);
        Route::get('clusters/{id}', [ClusterPickController::class, 'show']);
        Route::post('clusters/{id}/assign', [ClusterPickController::class, 'assign']);
        Route::post('clusters/{id}/start', [ClusterPickController::class, 'start']);
        Route::post('clusters/{id}/complete', [ClusterPickController::class, 'complete']);
    });
    
    // Packing Operations
    Route::prefix('packing')->group(function () {
        // Packing Stations
        Route::get('stations', [PackingController::class, 'getPackingStations']);
        Route::post('stations', [PackingController::class, 'createPackingStation']);
        Route::get('stations/{id}', [PackingController::class, 'getPackingStation']);
        Route::put('stations/{id}', [PackingController::class, 'updatePackingStation']);
        
        // Carton Types
        Route::get('cartons', [PackingController::class, 'getCartonTypes']);
        Route::post('cartons', [PackingController::class, 'createCartonType']);
        Route::post('cartons/recommend', [PackingController::class, 'getCartonRecommendation']);
        
        // Pack Orders
        Route::get('orders/pending', [PackingController::class, 'getPendingPackOrders']);
        Route::post('orders', [PackingController::class, 'createPackOrder']);
        Route::post('orders/{id}/start', [PackingController::class, 'startPacking']);
        
        // Packed Cartons
        Route::post('cartons', [PackingController::class, 'createPackedCarton']);
        Route::post('cartons/{id}/validate', [PackingController::class, 'validatePackedCarton']);
        Route::post('cartons/{id}/quality-check', [PackingController::class, 'qualityCheckCarton']);
        
        // Multi-Carton Shipments
        Route::post('multi-carton', [PackingController::class, 'createMultiCartonShipment']);
        
        // Packing Materials
        Route::get('materials', [PackingController::class, 'getPackingMaterials']);
        Route::put('materials/{id}/inventory', [PackingController::class, 'updatePackingMaterialInventory']);
        
        // Legacy routes for backward compatibility
        Route::get('stations', [PackingStationController::class, 'index']);
        Route::post('stations', [PackingStationController::class, 'store']);
        Route::get('stations/{id}', [PackingStationController::class, 'show']);
        Route::put('stations/{id}', [PackingStationController::class, 'update']);
        Route::delete('stations/{id}', [PackingStationController::class, 'destroy']);
        Route::get('stations/{id}/performance', [PackingStationController::class, 'performance']);
        
        Route::get('cartons', [CartonTypeController::class, 'index']);
        Route::post('cartons', [CartonTypeController::class, 'store']);
        Route::get('cartons/{id}', [CartonTypeController::class, 'show']);
        Route::put('cartons/{id}', [CartonTypeController::class, 'update']);
        Route::delete('cartons/{id}', [CartonTypeController::class, 'destroy']);
        Route::post('cartons/select-optimal', [CartonTypeController::class, 'selectOptimal']);
        
        Route::get('orders', [PackOrderController::class, 'index']);
        Route::post('orders', [PackOrderController::class, 'store']);
        Route::get('orders/{id}', [PackOrderController::class, 'show']);
        Route::put('orders/{id}', [PackOrderController::class, 'update']);
        Route::post('orders/{id}/assign', [PackOrderController::class, 'assign']);
        Route::post('orders/{id}/start', [PackOrderController::class, 'start']);
        Route::post('orders/{id}/pack', [PackOrderController::class, 'pack']);
        Route::post('orders/{id}/complete', [PackOrderController::class, 'complete']);
        Route::post('orders/{id}/validate', [PackOrderController::class, 'validate']);
        Route::get('orders/{id}/performance', [PackOrderController::class, 'performance']);
    });
    
    // Shipping & Loading
    Route::prefix('shipping')->group(function () {
        // Shipments
        Route::get('shipments', [ShippingController::class, 'getShipments']);
        Route::post('shipments', [ShippingController::class, 'createShipment']);
        Route::get('shipments/{id}', [ShippingController::class, 'getShipment']);
        Route::put('shipments/{id}', [ShippingController::class, 'updateShipment']);
        
        // Shipping Rates
        Route::get('rates', [ShippingController::class, 'getShippingRates']);
        Route::post('rates/shop', [ShippingController::class, 'performRateShopping']);
        
        // Shipping Documents & Labels
        Route::post('labels', [ShippingController::class, 'generateShippingLabel']);
        Route::post('documents', [ShippingController::class, 'generateShippingDocument']);
        
        // Shipping Manifests
        Route::post('manifests', [ShippingController::class, 'createShippingManifest']);
        Route::post('manifests/{id}/close', [ShippingController::class, 'closeShippingManifest']);
        Route::post('manifests/{id}/transmit', [ShippingController::class, 'transmitShippingManifest']);
        
        // Advanced Analytics Routes
        Route::get('analytics/customer', [ShippingController::class, 'getCustomerAnalytics']);
        Route::get('analytics/carrier-performance', [ShippingController::class, 'getCarrierPerformance']);
        Route::get('analytics/predictive-forecast', [ShippingController::class, 'getPredictiveForecast']);
        
        // Delivery Confirmations
        Route::post('delivery-confirmations', [ShippingController::class, 'recordDeliveryConfirmation']);
        
        // Load Planning
        Route::get('loads', [LoadPlanningController::class, 'getLoadPlans']);
        Route::post('loads', [LoadPlanningController::class, 'createLoadPlan']);
        Route::get('loads/{id}', [LoadPlanningController::class, 'getLoadPlan']);
        Route::put('loads/{id}', [LoadPlanningController::class, 'updateLoadPlan']);
        Route::post('loads/{id}/cancel', [LoadPlanningController::class, 'cancelLoadPlan']);
        Route::post('loads/confirm-loading', [LoadPlanningController::class, 'confirmLoading']);
        Route::get('loads/utilization', [LoadPlanningController::class, 'getDockUtilizationMetrics']);
        
        // Dock Scheduling
        Route::get('docks', [DockSchedulingController::class, 'getLoadingDocks']);
        Route::post('docks', [DockSchedulingController::class, 'createLoadingDock']);
        Route::get('docks/{id}', [DockSchedulingController::class, 'getLoadingDock']);
        Route::put('docks/{id}', [DockSchedulingController::class, 'updateLoadingDock']);
        
        Route::get('dock-schedules', [DockSchedulingController::class, 'getDockSchedules']);
        Route::post('dock-schedules', [DockSchedulingController::class, 'createDockSchedule']);
        Route::get('dock-schedules/{id}', [DockSchedulingController::class, 'getDockSchedule']);
        Route::put('dock-schedules/{id}', [DockSchedulingController::class, 'updateDockSchedule']);
        Route::post('dock-schedules/{id}/cancel', [DockSchedulingController::class, 'cancelDockSchedule']);
        
        Route::get('dock-availability', [DockSchedulingController::class, 'getDockAvailability']);
        Route::get('dock-slots', [DockSchedulingController::class, 'findAvailableDockSlots']);
        Route::get('dock-calendar', [DockSchedulingController::class, 'getDockScheduleCalendar']);
        
        // Legacy routes for backward compatibility
        Route::get('shipments', [ShipmentController::class, 'index']);
        Route::post('shipments', [ShipmentController::class, 'store']);
        Route::get('shipments/{id}', [ShipmentController::class, 'show']);
        Route::put('shipments/{id}', [ShipmentController::class, 'update']);
        Route::post('shipments/{id}/plan', [ShipmentController::class, 'plan']);
        Route::post('shipments/{id}/labels', [ShipmentController::class, 'generateLabels']);
        Route::post('shipments/{id}/documents', [ShipmentController::class, 'generateDocuments']);
        Route::post('shipments/{id}/manifest', [ShipmentController::class, 'addToManifest']);
        Route::get('shipments/{id}/tracking', [ShipmentController::class, 'getTracking']);
        
        Route::get('rates', [ShippingRateController::class, 'index']);
        Route::post('rates/shop', [ShippingRateController::class, 'shopRates']);
        Route::post('rates/compare', [ShippingRateController::class, 'compareRates']);
        Route::get('rates/carriers/{carrierId}', [ShippingRateController::class, 'getCarrierRates']);
        
        Route::get('loads', [LoadPlanController::class, 'index']);
        Route::post('loads', [LoadPlanController::class, 'store']);
        Route::get('loads/{id}', [LoadPlanController::class, 'show']);
        Route::put('loads/{id}', [LoadPlanController::class, 'update']);
        Route::post('loads/{id}/optimize', [LoadPlanController::class, 'optimize']);
        Route::post('loads/{id}/confirm-loading', [LoadPlanController::class, 'confirmLoading']);
        Route::post('loads/{id}/dispatch', [LoadPlanController::class, 'dispatch']);
        
        Route::get('docks/schedules', [DockScheduleController::class, 'index']);
        Route::post('docks/schedules', [DockScheduleController::class, 'store']);
        Route::get('docks/schedules/{id}', [DockScheduleController::class, 'show']);
        Route::put('docks/schedules/{id}', [DockScheduleController::class, 'update']);
        Route::post('docks/schedules/{id}/confirm', [DockScheduleController::class, 'confirm']);
        Route::post('docks/schedules/{id}/complete', [DockScheduleController::class, 'complete']);
        Route::get('docks/availability', [DockScheduleController::class, 'getAvailability']);
    });
    
    // Quality Control
    Route::prefix('quality-control')->group(function () {
        // Quality Checkpoints
        Route::get('checkpoints', [QualityControlController::class, 'getQualityCheckpoints']);
        Route::post('checkpoints', [QualityControlController::class, 'createQualityCheckpoint']);
        
        // Quality Checks
        Route::post('checks', [QualityControlController::class, 'performQualityCheck']);
        
        // Weight & Dimension Verification
        Route::post('weight-verification', [QualityControlController::class, 'verifyWeight']);
        Route::post('dimension-verification', [QualityControlController::class, 'verifyDimensions']);
        
        // Damage Inspection
        Route::post('damage-inspection', [QualityControlController::class, 'performDamageInspection']);
        
        // Quality Exceptions
        Route::get('exceptions', [QualityControlController::class, 'getQualityExceptions']);
        Route::post('exceptions/{id}/resolve', [QualityControlController::class, 'resolveQualityException']);
        
        // Quality Metrics
        Route::get('metrics', [QualityControlController::class, 'getQualityMetrics']);
    });
    
    // Analytics & Reporting
    Route::prefix('analytics')->group(function () {
        Route::get('performance/picking', [PickListController::class, 'pickingPerformance']);
        Route::get('performance/packing', [PackOrderController::class, 'packingPerformance']);
        Route::get('performance/shipping', [ShipmentController::class, 'shippingPerformance']);
        Route::get('kpis/outbound', [OutboundAnalyticsController::class, 'getOutboundKPIs']);
        Route::get('reports/daily-summary', [OutboundAnalyticsController::class, 'getDailySummary']);
    });
});

// Mobile API Routes for Outbound Operations
Route::prefix('mobile/outbound')->group(function () {
    // Picking
    Route::get('pick-lists/assigned/{employeeId}', [PickListController::class, 'getAssignedPickLists']);
    Route::post('pick-lists/{id}/scan-item', [PickListController::class, 'scanAndPick']);
    
    // Packing
    Route::get('pack-orders/assigned/{employeeId}', [PackOrderController::class, 'getAssignedPackOrders']);
    Route::post('pack-orders/{id}/scan-carton', [PackOrderController::class, 'scanAndPack']);
    Route::post('packing/scan-carton', [PackingController::class, 'createPackedCarton']);
    Route::post('packing/validate-carton/{id}', [PackingController::class, 'validatePackedCarton']);
    
    // Shipping
    Route::post('shipments/{id}/scan-label', [ShipmentController::class, 'scanLabel']);
    Route::post('shipping/scan-label', [ShippingController::class, 'generateShippingLabel']);
    Route::post('shipping/confirm-loading', [LoadPlanningController::class, 'confirmLoading']);
    
    // Quality Control
    Route::post('quality/check', [QualityControlController::class, 'performQualityCheck']);
    Route::post('quality/damage-inspection', [QualityControlController::class, 'performDamageInspection']);
});