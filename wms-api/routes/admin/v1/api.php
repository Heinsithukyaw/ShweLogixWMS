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

});
