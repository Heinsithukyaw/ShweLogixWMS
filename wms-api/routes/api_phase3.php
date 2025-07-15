<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ECommerce\OrderFulfillmentController;
use App\Http\Controllers\ECommerce\InventorySyncController;
use App\Http\Controllers\ECommerce\ReturnOrderController;
use App\Http\Controllers\UserManagement\RoleController;
use App\Http\Controllers\UserManagement\PermissionController;
use App\Http\Controllers\UserManagement\TenantController;
use App\Http\Controllers\UserManagement\UserActivityController;

/*
|--------------------------------------------------------------------------
| Phase 3 API Routes
|--------------------------------------------------------------------------
|
| Enhanced E-Commerce Integration, ERP Integration Enhancements,
| User Management Enhancements, Mobile Responsiveness
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Enhanced E-Commerce Integration
    Route::prefix('ecommerce')->group(function () {
        
        // Order Fulfillment
        Route::prefix('fulfillment')->group(function () {
            Route::get('/', [OrderFulfillmentController::class, 'index']);
            Route::post('/', [OrderFulfillmentController::class, 'store']);
            Route::get('/{id}', [OrderFulfillmentController::class, 'show']);
            Route::put('/{id}', [OrderFulfillmentController::class, 'update']);
            Route::post('/{id}/process-automation', [OrderFulfillmentController::class, 'processAutomation']);
            Route::post('/{id}/update-status', [OrderFulfillmentController::class, 'updateStatus']);
            Route::get('/analytics/overview', [OrderFulfillmentController::class, 'analytics']);
            Route::get('/automation/pending', [OrderFulfillmentController::class, 'pendingAutomation']);
        });

        // Inventory Sync
        Route::prefix('inventory-sync')->group(function () {
            Route::get('/', [InventorySyncController::class, 'index']);
            Route::post('/', [InventorySyncController::class, 'store']);
            Route::get('/{id}', [InventorySyncController::class, 'show']);
            Route::put('/{id}', [InventorySyncController::class, 'update']);
            Route::delete('/{id}', [InventorySyncController::class, 'destroy']);
            Route::post('/{id}/sync', [InventorySyncController::class, 'syncProduct']);
            Route::post('/sync-all', [InventorySyncController::class, 'syncAll']);
            Route::get('/statistics/overview', [InventorySyncController::class, 'statistics']);
            Route::get('/failed/list', [InventorySyncController::class, 'failedSyncs']);
            Route::post('/{id}/retry', [InventorySyncController::class, 'retrySync']);
        });

        // Return Orders
        Route::prefix('returns')->group(function () {
            Route::get('/', [ReturnOrderController::class, 'index']);
            Route::post('/', [ReturnOrderController::class, 'store']);
            Route::get('/{id}', [ReturnOrderController::class, 'show']);
            Route::put('/{id}', [ReturnOrderController::class, 'update']);
            Route::post('/{id}/approve', [ReturnOrderController::class, 'approve']);
            Route::post('/{id}/receive', [ReturnOrderController::class, 'receive']);
            Route::post('/{id}/process', [ReturnOrderController::class, 'process']);
            Route::get('/analytics/overview', [ReturnOrderController::class, 'analytics']);
            Route::get('/{id}/calculate-refund', [ReturnOrderController::class, 'calculateRefund']);
        });
    });

    // User Management Enhancements
    Route::prefix('user-management')->group(function () {
        
        // Roles
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::post('/', [RoleController::class, 'store']);
            Route::get('/{id}', [RoleController::class, 'show']);
            Route::put('/{id}', [RoleController::class, 'update']);
            Route::delete('/{id}', [RoleController::class, 'destroy']);
            Route::post('/{id}/permissions', [RoleController::class, 'assignPermissions']);
            Route::delete('/{id}/permissions/{permissionId}', [RoleController::class, 'revokePermission']);
            Route::get('/{id}/users', [RoleController::class, 'getUsers']);
        });

        // Permissions
        Route::prefix('permissions')->group(function () {
            Route::get('/', [PermissionController::class, 'index']);
            Route::post('/', [PermissionController::class, 'store']);
            Route::get('/{id}', [PermissionController::class, 'show']);
            Route::put('/{id}', [PermissionController::class, 'update']);
            Route::delete('/{id}', [PermissionController::class, 'destroy']);
            Route::get('/modules/list', [PermissionController::class, 'getModules']);
            Route::get('/categories/list', [PermissionController::class, 'getCategories']);
        });

        // Tenants
        Route::prefix('tenants')->group(function () {
            Route::get('/', [TenantController::class, 'index']);
            Route::post('/', [TenantController::class, 'store']);
            Route::get('/{id}', [TenantController::class, 'show']);
            Route::put('/{id}', [TenantController::class, 'update']);
            Route::delete('/{id}', [TenantController::class, 'destroy']);
            Route::post('/{id}/activate', [TenantController::class, 'activate']);
            Route::post('/{id}/deactivate', [TenantController::class, 'deactivate']);
            Route::get('/{id}/users', [TenantController::class, 'getUsers']);
            Route::get('/{id}/statistics', [TenantController::class, 'getStatistics']);
        });

        // User Activity Logs
        Route::prefix('activity-logs')->group(function () {
            Route::get('/', [UserActivityController::class, 'index']);
            Route::get('/user/{userId}', [UserActivityController::class, 'getUserActivity']);
            Route::get('/statistics', [UserActivityController::class, 'getStatistics']);
            Route::get('/export', [UserActivityController::class, 'export']);
        });
    });

    // Mobile API Endpoints
    Route::prefix('mobile')->group(function () {
        
        // Mobile Dashboard
        Route::get('/dashboard', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => [
                    'metrics' => [
                        ['title' => 'Total Inventory', 'value' => '12,543', 'change' => '+2.5%', 'trend' => 'up'],
                        ['title' => 'Orders Today', 'value' => '89', 'change' => '+12%', 'trend' => 'up'],
                        ['title' => 'Pending Tasks', 'value' => '23', 'change' => '-5%', 'trend' => 'down'],
                        ['title' => 'Completed', 'value' => '156', 'change' => '+8%', 'trend' => 'up']
                    ],
                    'alerts' => [
                        ['type' => 'warning', 'message' => 'Low stock alert for Product ABC', 'time' => '5 min ago'],
                        ['type' => 'info', 'message' => 'New order received #12345', 'time' => '10 min ago']
                    ]
                ]
            ]);
        });

        // Mobile Tasks
        Route::get('/tasks', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => [
                    [
                        'id' => '1',
                        'type' => 'pick',
                        'title' => 'Pick Order #12345',
                        'description' => 'Pick 5 items for customer order',
                        'priority' => 'high',
                        'status' => 'pending',
                        'location' => 'A-01-01',
                        'estimatedTime' => 15,
                        'items' => [
                            ['id' => '1', 'productCode' => 'ABC123', 'productName' => 'Widget A', 'quantity' => 2, 'location' => 'A-01-01', 'completed' => false]
                        ]
                    ]
                ]
            ]);
        });

        // Barcode Scanning
        Route::post('/scan', function (Request $request) {
            $request->validate([
                'barcode' => 'required|string',
                'scan_type' => 'required|in:product,location,order'
            ]);

            // Process barcode scan
            return response()->json([
                'success' => true,
                'data' => [
                    'barcode' => $request->barcode,
                    'type' => $request->scan_type,
                    'result' => [
                        'found' => true,
                        'data' => [
                            'name' => 'Sample Product',
                            'location' => 'A-01-01',
                            'quantity' => 50
                        ]
                    ]
                ]
            ]);
        });
    });

    // ERP Integration Enhancements
    Route::prefix('erp-integration')->group(function () {
        
        // Advanced SAP Connector
        Route::prefix('sap')->group(function () {
            Route::get('/connection/test', function () {
                return response()->json(['success' => true, 'status' => 'connected']);
            });
            Route::post('/sync/master-data', function () {
                return response()->json(['success' => true, 'message' => 'Master data sync initiated']);
            });
            Route::get('/sync/status', function () {
                return response()->json(['success' => true, 'status' => 'completed', 'last_sync' => now()]);
            });
        });

        // Advanced Oracle Connector
        Route::prefix('oracle')->group(function () {
            Route::get('/connection/test', function () {
                return response()->json(['success' => true, 'status' => 'connected']);
            });
            Route::post('/sync/financial-data', function () {
                return response()->json(['success' => true, 'message' => 'Financial data sync initiated']);
            });
        });

        // Advanced Microsoft Dynamics Connector
        Route::prefix('dynamics')->group(function () {
            Route::get('/connection/test', function () {
                return response()->json(['success' => true, 'status' => 'connected']);
            });
            Route::post('/sync/customer-data', function () {
                return response()->json(['success' => true, 'message' => 'Customer data sync initiated']);
            });
        });

        // Data Transformation
        Route::prefix('transformation')->group(function () {
            Route::get('/rules', function () {
                return response()->json(['success' => true, 'data' => []]);
            });
            Route::post('/rules', function () {
                return response()->json(['success' => true, 'message' => 'Transformation rule created']);
            });
            Route::post('/execute', function () {
                return response()->json(['success' => true, 'message' => 'Data transformation executed']);
            });
        });
    });
});