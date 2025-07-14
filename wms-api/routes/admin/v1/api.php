<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\api\v1\auth\AuthController;
use App\Http\Controllers\Admin\api\v1\uom\UnitOfMeasureController;
use App\Http\Controllers\Admin\api\v1\category\CategoryController;
use App\Http\Controllers\Admin\api\v1\brand\BrandController;
use App\Http\Controllers\Admin\api\v1\product\ProductController;
use App\Http\Controllers\Admin\api\v1\product\ProductInventoryController;
use App\Http\Controllers\Admin\api\v1\product\ProductDimensionController;
use App\Http\Controllers\Admin\api\v1\product\ProductCommercialController;
use App\Http\Controllers\Admin\api\v1\product\ProductOtherController;
use App\Http\Controllers\Admin\api\v1\business\BusinessPartyController;
use App\Http\Controllers\Admin\api\v1\business\BusinessContactController;
use App\Http\Controllers\Admin\api\v1\warehouse\WarehouseController;
use App\Http\Controllers\Admin\api\v1\warehouse\AreaController;
use App\Http\Controllers\Admin\api\v1\warehouse\ZoneController;
use App\Http\Controllers\Admin\api\v1\warehouse\LocationController;
use App\Http\Controllers\Admin\api\v1\warehouse\WarehouseLayoutController;
use App\Http\Controllers\Admin\api\v1\equipment\MaterialHandlingEqController;
use App\Http\Controllers\Admin\api\v1\equipment\StorageEquipmentController;
use App\Http\Controllers\Admin\api\v1\equipment\PalletEquipmentController;
use App\Http\Controllers\Admin\api\v1\equipment\DockEquipmentController;
use App\Http\Controllers\Admin\api\v1\employee\EmployeeController;
use App\Http\Controllers\Admin\api\v1\order\OrderTypeController;
use App\Http\Controllers\Admin\api\v1\shipping\ShippingCarrierController;
use App\Http\Controllers\Admin\api\v1\financial\FinancialCategoryController;
use App\Http\Controllers\Admin\api\v1\financial\CostTypeController;
use App\Http\Controllers\Admin\api\v1\financial\CurrencyController;
use App\Http\Controllers\Admin\api\v1\financial\TaxController;
use App\Http\Controllers\Admin\api\v1\financial\PaymentTermController;
use App\Http\Controllers\Admin\api\v1\geographical\CountryController;
use App\Http\Controllers\Admin\api\v1\optimization\OptimizationMetricController;
use App\Http\Controllers\Admin\api\v1\optimization\PredictiveModelController;
use App\Http\Controllers\Admin\api\v1\iot\IoTDeviceController;
use App\Http\Controllers\Admin\api\v1\geographical\StateController;
use App\Http\Controllers\Admin\api\v1\geographical\CityController;
use App\Http\Controllers\Admin\api\v1\operational\StatusController;
use App\Http\Controllers\Admin\api\v1\operational\ActivityTypeController;
use App\Http\Controllers\Admin\api\v1\inbound\AdvancedShippingNoticeController;
use App\Http\Controllers\Admin\api\v1\inbound\AdvancedShippingNoticeDetailController;
use App\Http\Controllers\Admin\api\v1\inbound\InboundShipmentController;
use App\Http\Controllers\Admin\api\v1\inbound\InboundShipmentDetailController;
use App\Http\Controllers\Admin\api\v1\inbound\ReceivingAppointmentController;
use App\Http\Controllers\Admin\api\v1\inbound\UnloadingSessionController;
use App\Http\Controllers\Admin\api\v1\inbound\QualityInspectionController;
use App\Http\Controllers\Admin\api\v1\inbound\GoodReceivedNoteController;
use App\Http\Controllers\Admin\api\v1\inbound\GoodReceivedNoteItemController;
use App\Http\Controllers\Admin\api\v1\inbound\ReceivingExceptionController;
use App\Http\Controllers\Admin\api\v1\inbound\PutAwayTaskController;
use App\Http\Controllers\Admin\api\v1\inbound\CrossDockingTaskController;
use App\Http\Controllers\Admin\api\v1\inbound\ReceivingLaborTrackingController;
use App\Http\Controllers\Admin\api\v1\inbound\ReceivingDockController;
use App\Http\Controllers\Admin\api\v1\inbound\StagingLocationController;
use App\Http\Controllers\Admin\api\v1\inbound\ReceivingEquipmentController;
use App\Http\Controllers\Api\Admin\EventMonitoringController;
use App\Http\Controllers\Admin\api\v1\notification\NotificationController;
use App\Http\Controllers\Admin\IntegrationController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;


// Health check routes (no auth required)
Route::get('/health', function () {
    try {
        // Check database connection
        DB::connection()->getPdo();
        $database_status = 'connected';
    } catch (Exception $e) {
        $database_status = 'disconnected';
    }

    try {
        // Check Redis connection
        Redis::ping();
        $redis_status = 'connected';
    } catch (Exception $e) {
        $redis_status = 'disconnected';
    }

    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'services' => [
            'database' => $database_status,
            'redis' => $redis_status,
        ],
        'version' => '1.0.0'
    ]);
});

Route::get('/integration/status', function () {
    $integrations = [
        'sap' => env('SAP_INTEGRATION_ENABLED', false),
        'oracle' => env('ORACLE_INTEGRATION_ENABLED', false),
        'dynamics' => env('DYNAMICS_INTEGRATION_ENABLED', false),
        'shopify' => env('SHOPIFY_INTEGRATION_ENABLED', false),
        'magento' => env('MAGENTO_INTEGRATION_ENABLED', false),
        'woocommerce' => env('WOOCOMMERCE_INTEGRATION_ENABLED', false),
        'amazon' => env('AMAZON_INTEGRATION_ENABLED', false),
        'ebay' => env('EBAY_INTEGRATION_ENABLED', false),
        'walmart' => env('WALMART_INTEGRATION_ENABLED', false),
        'fedex' => env('FEDEX_INTEGRATION_ENABLED', false),
        'ups' => env('UPS_INTEGRATION_ENABLED', false),
        'dhl' => env('DHL_INTEGRATION_ENABLED', false),
        'quickbooks' => env('QUICKBOOKS_INTEGRATION_ENABLED', false),
        'xero' => env('XERO_INTEGRATION_ENABLED', false),
        'stripe' => env('STRIPE_INTEGRATION_ENABLED', false),
        'salesforce' => env('SALESFORCE_INTEGRATION_ENABLED', false),
        'hubspot' => env('HUBSPOT_INTEGRATION_ENABLED', false),
    ];

    $enabled_count = count(array_filter($integrations));
    $total_count = count($integrations);

    return response()->json([
        'status' => 'ok',
        'integrations' => $integrations,
        'summary' => [
            'enabled' => $enabled_count,
            'total' => $total_count,
            'percentage' => $total_count > 0 ? round(($enabled_count / $total_count) * 100, 2) : 0
        ],
        'timestamp' => now()->toISOString()
    ]);
});

Route::post('register', [AuthController::class, 'register'])->name('auth.register');
Route::post('login', [AuthController::class, 'login'])->name('auth.login');

Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/auth/google/token-login', [AuthController::class, 'googleTokenLogin']);

Route::middleware('auth:api')->group(function () {
    // Unit Of Measure Management
    Route::get('/get-base-uom-lists', [UnitOfMeasureController::class, 'getBaseUomLists'])->name('admin.uom.baseUomLists');
    Route::apiResource('unit_of_measures', UnitOfMeasureController::class);

    // Category Management
    Route::apiResource('categories', CategoryController::class);

    // Brand Management
    Route::apiResource('brands', BrandController::class);

    // Product Management
    Route::apiResource('products', ProductController::class);

    Route::apiResource('product-inventories', ProductInventoryController::class);

    Route::apiResource('product-dimensions', ProductDimensionController::class);

    Route::apiResource('product-commercials', ProductCommercialController::class);

    Route::apiResource('product-others', ProductOtherController::class);

    Route::apiResource('business-parties', BusinessPartyController::class);

    Route::apiResource('business-contacts', BusinessContactController::class);

    Route::apiResource('warehouses', WarehouseController::class);

    Route::apiResource('areas', AreaController::class);

    Route::apiResource('zones', ZoneController::class);

    Route::apiResource('locations', LocationController::class);

    Route::apiResource('material-handling-eqs', MaterialHandlingEqController::class);

    Route::apiResource('storage-equipments', StorageEquipmentController::class);

    Route::apiResource('pallet-equipments', PalletEquipmentController::class);

    Route::apiResource('dock-equipments', DockEquipmentController::class);

    Route::apiResource('employees', EmployeeController::class);

    Route::apiResource('order-types', OrderTypeController::class);

    Route::apiResource('shipping-carriers', ShippingCarrierController::class);

    Route::apiResource('financial-categories', FinancialCategoryController::class);

    Route::apiResource('cost-types', CostTypeController::class);

    Route::apiResource('currencies', CurrencyController::class);

    Route::apiResource('taxes', TaxController::class);

    Route::apiResource('payment-terms', PaymentTermController::class);

    Route::apiResource('countries', CountryController::class);

    Route::apiResource('states', StateController::class);

    Route::apiResource('cities', CityController::class);

    Route::apiResource('statuses', StatusController::class);

    Route::apiResource('activity-types', ActivityTypeController::class);

    Route::apiResource('advanced-shipping-notices', AdvancedShippingNoticeController::class);

    Route::apiResource('advanced-shipping-notice-detail', AdvancedShippingNoticeDetailController::class);

    Route::apiResource('inbound-shipments', InboundShipmentController::class);

    Route::apiResource('inbound-shipment-details', InboundShipmentDetailController::class);

    Route::apiResource('receiving-appointments', ReceivingAppointmentController::class);

    Route::apiResource('unloading-sessions', UnloadingSessionController::class);

    Route::apiResource('quality-inspections', QualityInspectionController::class);
    Route::post('quality-inspections/{id}', [QualityInspectionController::class, 'update']);


    Route::apiResource('good-received-notes', GoodReceivedNoteController::class);
    Route::apiResource('good-received-note-items', GoodReceivedNoteItemController::class);
    Route::post('update-good-received-note-items', [GoodReceivedNoteItemController::class, 'update']);

    Route::apiResource('receiving-exceptions', ReceivingExceptionController::class);
    Route::apiResource('put-away-tasks', PutAwayTaskController::class);
    Route::apiResource('cross-docking-tasks', CrossDockingTaskController::class);
    Route::apiResource('receiving-labor-trackings', ReceivingLaborTrackingController::class);

    Route::apiResource('receiving-docks', ReceivingDockController::class);
    Route::apiResource('staging-locations', StagingLocationController::class);
    Route::apiResource('receiving-equipments', ReceivingEquipmentController::class);

    // Notification Routes
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::post('notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);

    // Event Monitoring Routes
    Route::prefix('events')->group(function () {
        Route::get('statistics', [EventMonitoringController::class, 'getStatistics']);
        Route::get('performance', [EventMonitoringController::class, 'getPerformance']);
        Route::get('backlog', [EventMonitoringController::class, 'getBacklog']);
        Route::get('logs', [EventMonitoringController::class, 'getLogs']);
        Route::get('idempotency-statistics', [EventMonitoringController::class, 'getIdempotencyStatistics']);
        Route::get('dashboard-summary', [EventMonitoringController::class, 'getDashboardSummary']);
    });

    // Integration Management Routes
    Route::prefix('integrations')->group(function () {
        Route::get('/', [IntegrationController::class, 'index']);
        Route::post('/', [IntegrationController::class, 'store']);
        Route::get('/{id}', [IntegrationController::class, 'show']);
        Route::put('/{id}', [IntegrationController::class, 'update']);
        Route::delete('/{id}', [IntegrationController::class, 'destroy']);
        
        // Integration Actions
        Route::post('/{id}/test', [IntegrationController::class, 'testConnection']);
        Route::post('/{id}/sync', [IntegrationController::class, 'triggerSync']);
        Route::post('/{id}/enable', [IntegrationController::class, 'enable']);
        Route::post('/{id}/disable', [IntegrationController::class, 'disable']);
        
        // Integration Logs
        Route::get('/{id}/logs', [IntegrationController::class, 'getLogs']);
        Route::get('/{id}/sync-jobs', [IntegrationController::class, 'getSyncJobs']);
        
        // Webhook Management
        Route::get('/{id}/webhooks', [IntegrationController::class, 'getWebhooks']);
        Route::post('/{id}/webhooks', [IntegrationController::class, 'createWebhook']);
        Route::delete('/{id}/webhooks/{webhookId}', [IntegrationController::class, 'deleteWebhook']);
        
        // Data Mappings
        Route::get('/{id}/mappings', [IntegrationController::class, 'getMappings']);
        Route::post('/{id}/mappings', [IntegrationController::class, 'createMapping']);
        Route::put('/{id}/mappings/{mappingId}', [IntegrationController::class, 'updateMapping']);
        Route::delete('/{id}/mappings/{mappingId}', [IntegrationController::class, 'deleteMapping']);
    });

    // Warehouse Optimization Routes
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
});
