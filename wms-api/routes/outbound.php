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
use App\Http\Controllers\Outbound\PackingStationController;
use App\Http\Controllers\Outbound\PackOrderController;
use App\Http\Controllers\Outbound\CartonTypeController;
use App\Http\Controllers\Outbound\ShipmentController;
use App\Http\Controllers\Outbound\ShippingRateController;
use App\Http\Controllers\Outbound\LoadPlanController;
use App\Http\Controllers\Outbound\DockScheduleController;

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
        Route::get('stations', [PackingStationController::class, 'index']);
        Route::post('stations', [PackingStationController::class, 'store']);
        Route::get('stations/{id}', [PackingStationController::class, 'show']);
        Route::put('stations/{id}', [PackingStationController::class, 'update']);
        Route::delete('stations/{id}', [PackingStationController::class, 'destroy']);
        Route::get('stations/{id}/performance', [PackingStationController::class, 'performance']);
        
        // Carton Types
        Route::get('cartons', [CartonTypeController::class, 'index']);
        Route::post('cartons', [CartonTypeController::class, 'store']);
        Route::get('cartons/{id}', [CartonTypeController::class, 'show']);
        Route::put('cartons/{id}', [CartonTypeController::class, 'update']);
        Route::delete('cartons/{id}', [CartonTypeController::class, 'destroy']);
        Route::post('cartons/select-optimal', [CartonTypeController::class, 'selectOptimal']);
        
        // Pack Orders
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
        Route::get('shipments', [ShipmentController::class, 'index']);
        Route::post('shipments', [ShipmentController::class, 'store']);
        Route::get('shipments/{id}', [ShipmentController::class, 'show']);
        Route::put('shipments/{id}', [ShipmentController::class, 'update']);
        Route::post('shipments/{id}/plan', [ShipmentController::class, 'plan']);
        Route::post('shipments/{id}/labels', [ShipmentController::class, 'generateLabels']);
        Route::post('shipments/{id}/documents', [ShipmentController::class, 'generateDocuments']);
        Route::post('shipments/{id}/manifest', [ShipmentController::class, 'addToManifest']);
        Route::get('shipments/{id}/tracking', [ShipmentController::class, 'getTracking']);
        
        // Shipping Rates
        Route::get('rates', [ShippingRateController::class, 'index']);
        Route::post('rates/shop', [ShippingRateController::class, 'shopRates']);
        Route::post('rates/compare', [ShippingRateController::class, 'compareRates']);
        Route::get('rates/carriers/{carrierId}', [ShippingRateController::class, 'getCarrierRates']);
        
        // Load Planning
        Route::get('loads', [LoadPlanController::class, 'index']);
        Route::post('loads', [LoadPlanController::class, 'store']);
        Route::get('loads/{id}', [LoadPlanController::class, 'show']);
        Route::put('loads/{id}', [LoadPlanController::class, 'update']);
        Route::post('loads/{id}/optimize', [LoadPlanController::class, 'optimize']);
        Route::post('loads/{id}/confirm-loading', [LoadPlanController::class, 'confirmLoading']);
        Route::post('loads/{id}/dispatch', [LoadPlanController::class, 'dispatch']);
        
        // Dock Scheduling
        Route::get('docks/schedules', [DockScheduleController::class, 'index']);
        Route::post('docks/schedules', [DockScheduleController::class, 'store']);
        Route::get('docks/schedules/{id}', [DockScheduleController::class, 'show']);
        Route::put('docks/schedules/{id}', [DockScheduleController::class, 'update']);
        Route::post('docks/schedules/{id}/confirm', [DockScheduleController::class, 'confirm']);
        Route::post('docks/schedules/{id}/complete', [DockScheduleController::class, 'complete']);
        Route::get('docks/availability', [DockScheduleController::class, 'getAvailability']);
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
    Route::get('pick-lists/assigned/{employeeId}', [PickListController::class, 'getAssignedPickLists']);
    Route::post('pick-lists/{id}/scan-item', [PickListController::class, 'scanAndPick']);
    Route::get('pack-orders/assigned/{employeeId}', [PackOrderController::class, 'getAssignedPackOrders']);
    Route::post('pack-orders/{id}/scan-carton', [PackOrderController::class, 'scanAndPack']);
    Route::post('shipments/{id}/scan-label', [ShipmentController::class, 'scanLabel']);
});