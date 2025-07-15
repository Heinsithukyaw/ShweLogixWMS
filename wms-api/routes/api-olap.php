<?php

use Illuminate\Support\Facades\Route;

// OLAP Routes
Route::group(['prefix' => 'olap', 'namespace' => 'App\Http\Controllers\OLAP'], function () {
    // Inventory Movement Facts
    Route::get('inventory-movements', 'OlapFactInventoryMovementController@index');
    Route::get('inventory-movements/{id}', 'OlapFactInventoryMovementController@show');
    Route::get('inventory-movements/aggregate/by-product', 'OlapFactInventoryMovementController@aggregateByProduct');
    Route::get('inventory-movements/aggregate/by-warehouse', 'OlapFactInventoryMovementController@aggregateByWarehouse');
    Route::get('inventory-movements/aggregate/by-date', 'OlapFactInventoryMovementController@aggregateByDate');
    Route::get('inventory-movements/aggregate/by-movement-type', 'OlapFactInventoryMovementController@aggregateByMovementType');
    Route::get('inventory-movements/trends/daily', 'OlapFactInventoryMovementController@dailyTrends');
    Route::get('inventory-movements/trends/weekly', 'OlapFactInventoryMovementController@weeklyTrends');
    Route::get('inventory-movements/trends/monthly', 'OlapFactInventoryMovementController@monthlyTrends');
    
    // Order Processing Facts
    Route::get('order-processing', 'OlapFactOrderProcessingController@index');
    Route::get('order-processing/{id}', 'OlapFactOrderProcessingController@show');
    Route::get('order-processing/aggregate/by-customer', 'OlapFactOrderProcessingController@aggregateByCustomer');
    Route::get('order-processing/aggregate/by-product', 'OlapFactOrderProcessingController@aggregateByProduct');
    Route::get('order-processing/aggregate/by-date', 'OlapFactOrderProcessingController@aggregateByDate');
    Route::get('order-processing/aggregate/by-status', 'OlapFactOrderProcessingController@aggregateByStatus');
    Route::get('order-processing/trends/daily', 'OlapFactOrderProcessingController@dailyTrends');
    Route::get('order-processing/trends/weekly', 'OlapFactOrderProcessingController@weeklyTrends');
    Route::get('order-processing/trends/monthly', 'OlapFactOrderProcessingController@monthlyTrends');
    Route::get('order-processing/metrics/fulfillment-time', 'OlapFactOrderProcessingController@fulfillmentTimeMetrics');
    Route::get('order-processing/metrics/order-accuracy', 'OlapFactOrderProcessingController@orderAccuracyMetrics');
    
    // Warehouse Operation Facts
    Route::get('warehouse-operations', 'OlapFactWarehouseOperationController@index');
    Route::get('warehouse-operations/{id}', 'OlapFactWarehouseOperationController@show');
    Route::get('warehouse-operations/aggregate/by-warehouse', 'OlapFactWarehouseOperationController@aggregateByWarehouse');
    Route::get('warehouse-operations/aggregate/by-operation-type', 'OlapFactWarehouseOperationController@aggregateByOperationType');
    Route::get('warehouse-operations/aggregate/by-user', 'OlapFactWarehouseOperationController@aggregateByUser');
    Route::get('warehouse-operations/aggregate/by-date', 'OlapFactWarehouseOperationController@aggregateByDate');
    Route::get('warehouse-operations/trends/daily', 'OlapFactWarehouseOperationController@dailyTrends');
    Route::get('warehouse-operations/trends/weekly', 'OlapFactWarehouseOperationController@weeklyTrends');
    Route::get('warehouse-operations/trends/monthly', 'OlapFactWarehouseOperationController@monthlyTrends');
    Route::get('warehouse-operations/metrics/productivity', 'OlapFactWarehouseOperationController@productivityMetrics');
    Route::get('warehouse-operations/metrics/error-rates', 'OlapFactWarehouseOperationController@errorRateMetrics');
    
    // Time Dimension
    Route::get('dimensions/time', 'OlapDimTimeController@index');
    Route::get('dimensions/time/{id}', 'OlapDimTimeController@show');
    Route::get('dimensions/time/year/{year}', 'OlapDimTimeController@getByYear');
    Route::get('dimensions/time/month/{year}/{month}', 'OlapDimTimeController@getByMonth');
    Route::get('dimensions/time/day/{year}/{month}/{day}', 'OlapDimTimeController@getByDay');
    Route::get('dimensions/time/quarter/{year}/{quarter}', 'OlapDimTimeController@getByQuarter');
    
    // Product Dimension
    Route::get('dimensions/product', 'OlapDimProductController@index');
    Route::get('dimensions/product/{id}', 'OlapDimProductController@show');
    Route::get('dimensions/product/category/{category}', 'OlapDimProductController@getByCategory');
    Route::get('dimensions/product/brand/{brand}', 'OlapDimProductController@getByBrand');
    Route::get('dimensions/product/supplier/{supplier}', 'OlapDimProductController@getBySupplier');
    
    // Customer Dimension
    Route::get('dimensions/customer', 'OlapDimCustomerController@index');
    Route::get('dimensions/customer/{id}', 'OlapDimCustomerController@show');
    Route::get('dimensions/customer/segment/{segment}', 'OlapDimCustomerController@getBySegment');
    Route::get('dimensions/customer/region/{region}', 'OlapDimCustomerController@getByRegion');
    Route::get('dimensions/customer/type/{type}', 'OlapDimCustomerController@getByType');
    
    // Warehouse Dimension
    Route::get('dimensions/warehouse', 'OlapDimWarehouseController@index');
    Route::get('dimensions/warehouse/{id}', 'OlapDimWarehouseController@show');
    Route::get('dimensions/warehouse/region/{region}', 'OlapDimWarehouseController@getByRegion');
    Route::get('dimensions/warehouse/type/{type}', 'OlapDimWarehouseController@getByType');
    
    // Dashboard Metrics
    Route::get('dashboard/inventory-summary', 'OlapDashboardController@inventorySummary');
    Route::get('dashboard/order-summary', 'OlapDashboardController@orderSummary');
    Route::get('dashboard/warehouse-performance', 'OlapDashboardController@warehousePerformance');
    Route::get('dashboard/kpi-metrics', 'OlapDashboardController@kpiMetrics');
    Route::get('dashboard/trend-analysis', 'OlapDashboardController@trendAnalysis');
});