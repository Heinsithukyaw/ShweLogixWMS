<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Returns\ReturnAuthorizationController;
use App\Http\Controllers\Returns\ReturnReceiptController;
use App\Http\Controllers\Returns\ReverseLogisticsOrderController;
use App\Http\Controllers\Labor\LaborScheduleController;
use App\Http\Controllers\Labor\LaborTaskController;
use App\Http\Controllers\Labor\LaborPerformanceController;
use App\Http\Controllers\Equipment\EquipmentRegistryController;
use App\Http\Controllers\Equipment\EquipmentMaintenanceController;
use App\Http\Controllers\Equipment\EquipmentUtilizationController;

/*
|--------------------------------------------------------------------------
| Phase 6 & 7 API Routes
|--------------------------------------------------------------------------
|
| Returns & Reverse Logistics (Phase 6)
| Advanced Labor Management (Phase 7)
| Equipment Management (Phase 7)
|
*/

Route::middleware(['auth:api'])->group(function () {
    
    // ========================================
    // RETURNS & REVERSE LOGISTICS (PHASE 6)
    // ========================================
    
    Route::prefix('returns')->group(function () {
        
        // Return Authorizations (RMA)
        Route::prefix('authorizations')->group(function () {
            Route::get('/', [ReturnAuthorizationController::class, 'index']);
            Route::post('/', [ReturnAuthorizationController::class, 'store']);
            Route::get('/{id}', [ReturnAuthorizationController::class, 'show']);
            Route::put('/{id}', [ReturnAuthorizationController::class, 'update']);
            Route::delete('/{id}', [ReturnAuthorizationController::class, 'destroy']);
            
            // RMA Actions
            Route::post('/{id}/approve', [ReturnAuthorizationController::class, 'approve']);
            Route::post('/{id}/reject', [ReturnAuthorizationController::class, 'reject']);
            Route::post('/{id}/cancel', [ReturnAuthorizationController::class, 'cancel']);
            
            // RMA Analytics
            Route::get('/analytics/summary', [ReturnAuthorizationController::class, 'analytics']);
            Route::get('/analytics/trends', [ReturnAuthorizationController::class, 'trends']);
        });
        
        // Return Receipts
        Route::prefix('receipts')->group(function () {
            Route::get('/', [ReturnReceiptController::class, 'index']);
            Route::post('/', [ReturnReceiptController::class, 'store']);
            Route::get('/{id}', [ReturnReceiptController::class, 'show']);
            Route::put('/{id}', [ReturnReceiptController::class, 'update']);
            
            // Receipt Actions
            Route::post('/{id}/quality-check', [ReturnReceiptController::class, 'qualityCheck']);
            Route::post('/{id}/process-disposition', [ReturnReceiptController::class, 'processDisposition']);
        });
        
        // Reverse Logistics Orders
        Route::prefix('reverse-logistics')->group(function () {
            Route::get('/', [ReverseLogisticsOrderController::class, 'index']);
            Route::post('/', [ReverseLogisticsOrderController::class, 'store']);
            Route::get('/{id}', [ReverseLogisticsOrderController::class, 'show']);
            Route::put('/{id}', [ReverseLogisticsOrderController::class, 'update']);
            
            // Reverse Logistics Actions
            Route::post('/{id}/approve', [ReverseLogisticsOrderController::class, 'approve']);
            Route::post('/{id}/ship', [ReverseLogisticsOrderController::class, 'ship']);
            Route::post('/{id}/complete', [ReverseLogisticsOrderController::class, 'complete']);
        });
        
        // Return Analytics & Reporting
        Route::prefix('analytics')->group(function () {
            Route::get('/dashboard', [ReturnAuthorizationController::class, 'dashboard']);
            Route::get('/return-rates', [ReturnAuthorizationController::class, 'returnRates']);
            Route::get('/cost-analysis', [ReturnAuthorizationController::class, 'costAnalysis']);
            Route::get('/recovery-rates', [ReturnAuthorizationController::class, 'recoveryRates']);
        });
    });
    
    // ========================================
    // ADVANCED LABOR MANAGEMENT (PHASE 7)
    // ========================================
    
    Route::prefix('labor')->group(function () {
        
        // Labor Shifts
        Route::prefix('shifts')->group(function () {
            Route::get('/', [LaborShiftController::class, 'index']);
            Route::post('/', [LaborShiftController::class, 'store']);
            Route::get('/{id}', [LaborShiftController::class, 'show']);
            Route::put('/{id}', [LaborShiftController::class, 'update']);
            Route::delete('/{id}', [LaborShiftController::class, 'destroy']);
        });
        
        // Labor Schedules
        Route::prefix('schedules')->group(function () {
            Route::get('/', [LaborScheduleController::class, 'index']);
            Route::post('/', [LaborScheduleController::class, 'store']);
            Route::get('/{id}', [LaborScheduleController::class, 'show']);
            Route::put('/{id}', [LaborScheduleController::class, 'update']);
            Route::delete('/{id}', [LaborScheduleController::class, 'destroy']);
            
            // Schedule Actions
            Route::post('/{id}/check-in', [LaborScheduleController::class, 'checkIn']);
            Route::post('/{id}/check-out', [LaborScheduleController::class, 'checkOut']);
            Route::post('/{id}/break-start', [LaborScheduleController::class, 'breakStart']);
            Route::post('/{id}/break-end', [LaborScheduleController::class, 'breakEnd']);
            
            // Bulk Operations
            Route::post('/bulk-create', [LaborScheduleController::class, 'bulkCreate']);
            Route::post('/bulk-update', [LaborScheduleController::class, 'bulkUpdate']);
        });
        
        // Labor Tasks
        Route::prefix('tasks')->group(function () {
            Route::get('/', [LaborTaskController::class, 'index']);
            Route::post('/', [LaborTaskController::class, 'store']);
            Route::get('/{id}', [LaborTaskController::class, 'show']);
            Route::put('/{id}', [LaborTaskController::class, 'update']);
            Route::delete('/{id}', [LaborTaskController::class, 'destroy']);
            
            // Task Actions
            Route::post('/{id}/assign', [LaborTaskController::class, 'assign']);
            Route::post('/{id}/start', [LaborTaskController::class, 'start']);
            Route::post('/{id}/complete', [LaborTaskController::class, 'complete']);
            Route::post('/{id}/cancel', [LaborTaskController::class, 'cancel']);
            
            // Task Analytics
            Route::get('/analytics/productivity', [LaborTaskController::class, 'productivityAnalytics']);
            Route::get('/analytics/completion-rates', [LaborTaskController::class, 'completionRates']);
        });
        
        // Labor Performance
        Route::prefix('performance')->group(function () {
            Route::get('/', [LaborPerformanceController::class, 'index']);
            Route::get('/employee/{id}', [LaborPerformanceController::class, 'employeePerformance']);
            Route::get('/team/{id}', [LaborPerformanceController::class, 'teamPerformance']);
            Route::get('/warehouse/{id}', [LaborPerformanceController::class, 'warehousePerformance']);
            
            // Performance Reports
            Route::get('/reports/attendance', [LaborPerformanceController::class, 'attendanceReport']);
            Route::get('/reports/productivity', [LaborPerformanceController::class, 'productivityReport']);
            Route::get('/reports/cost-analysis', [LaborPerformanceController::class, 'costAnalysis']);
        });
        
        // Labor Skills Management
        Route::prefix('skills')->group(function () {
            Route::get('/', [LaborSkillController::class, 'index']);
            Route::post('/', [LaborSkillController::class, 'store']);
            Route::get('/{id}', [LaborSkillController::class, 'show']);
            Route::put('/{id}', [LaborSkillController::class, 'update']);
            
            // Employee Skills
            Route::get('/employee/{id}', [LaborSkillController::class, 'employeeSkills']);
            Route::post('/employee/{id}/assign', [LaborSkillController::class, 'assignSkill']);
            Route::delete('/employee/{employeeId}/skill/{skillId}', [LaborSkillController::class, 'removeSkill']);
        });
        
        // Labor Analytics & Reporting
        Route::prefix('analytics')->group(function () {
            Route::get('/dashboard', [LaborScheduleController::class, 'analytics']);
            Route::get('/attendance-trends', [LaborPerformanceController::class, 'attendanceTrends']);
            Route::get('/productivity-trends', [LaborPerformanceController::class, 'productivityTrends']);
            Route::get('/cost-trends', [LaborPerformanceController::class, 'costTrends']);
            Route::get('/skill-gaps', [LaborSkillController::class, 'skillGapAnalysis']);
        });
    });
    
    // ========================================
    // EQUIPMENT MANAGEMENT (PHASE 7)
    // ========================================
    
    Route::prefix('equipment')->group(function () {
        
        // Equipment Registry
        Route::prefix('registry')->group(function () {
            Route::get('/', [EquipmentRegistryController::class, 'index']);
            Route::post('/', [EquipmentRegistryController::class, 'store']);
            Route::get('/{id}', [EquipmentRegistryController::class, 'show']);
            Route::put('/{id}', [EquipmentRegistryController::class, 'update']);
            Route::delete('/{id}', [EquipmentRegistryController::class, 'destroy']);
            
            // Equipment Actions
            Route::post('/{id}/assign-operator', [EquipmentRegistryController::class, 'assignOperator']);
            Route::post('/{id}/relocate', [EquipmentRegistryController::class, 'relocate']);
            Route::post('/{id}/retire', [EquipmentRegistryController::class, 'retire']);
            Route::post('/{id}/dispose', [EquipmentRegistryController::class, 'dispose']);
            
            // Equipment Search & Filters
            Route::get('/search', [EquipmentRegistryController::class, 'search']);
            Route::get('/available', [EquipmentRegistryController::class, 'available']);
            Route::get('/maintenance-due', [EquipmentRegistryController::class, 'maintenanceDue']);
        });
        
        // Equipment Categories
        Route::prefix('categories')->group(function () {
            Route::get('/', [EquipmentCategoryController::class, 'index']);
            Route::post('/', [EquipmentCategoryController::class, 'store']);
            Route::get('/{id}', [EquipmentCategoryController::class, 'show']);
            Route::put('/{id}', [EquipmentCategoryController::class, 'update']);
            Route::delete('/{id}', [EquipmentCategoryController::class, 'destroy']);
        });
        
        // Equipment Maintenance
        Route::prefix('maintenance')->group(function () {
            Route::get('/', [EquipmentMaintenanceController::class, 'index']);
            Route::post('/', [EquipmentMaintenanceController::class, 'store']);
            Route::get('/{id}', [EquipmentMaintenanceController::class, 'show']);
            Route::put('/{id}', [EquipmentMaintenanceController::class, 'update']);
            
            // Maintenance Actions
            Route::post('/{id}/start', [EquipmentMaintenanceController::class, 'start']);
            Route::post('/{id}/complete', [EquipmentMaintenanceController::class, 'complete']);
            Route::post('/{id}/defer', [EquipmentMaintenanceController::class, 'defer']);
            Route::post('/{id}/cancel', [EquipmentMaintenanceController::class, 'cancel']);
            
            // Maintenance Schedules
            Route::get('/schedules', [EquipmentMaintenanceController::class, 'schedules']);
            Route::post('/schedules', [EquipmentMaintenanceController::class, 'createSchedule']);
            Route::put('/schedules/{id}', [EquipmentMaintenanceController::class, 'updateSchedule']);
            
            // Maintenance Analytics
            Route::get('/analytics/costs', [EquipmentMaintenanceController::class, 'costAnalytics']);
            Route::get('/analytics/downtime', [EquipmentMaintenanceController::class, 'downtimeAnalytics']);
        });
        
        // Equipment Utilization
        Route::prefix('utilization')->group(function () {
            Route::get('/', [EquipmentUtilizationController::class, 'index']);
            Route::post('/start-session', [EquipmentUtilizationController::class, 'startSession']);
            Route::post('/end-session', [EquipmentUtilizationController::class, 'endSession']);
            Route::get('/active-sessions', [EquipmentUtilizationController::class, 'activeSessions']);
            
            // Utilization Reports
            Route::get('/reports/efficiency', [EquipmentUtilizationController::class, 'efficiencyReport']);
            Route::get('/reports/availability', [EquipmentUtilizationController::class, 'availabilityReport']);
            Route::get('/reports/performance', [EquipmentUtilizationController::class, 'performanceReport']);
        });
        
        // Equipment Inspections
        Route::prefix('inspections')->group(function () {
            Route::get('/', [EquipmentInspectionController::class, 'index']);
            Route::post('/', [EquipmentInspectionController::class, 'store']);
            Route::get('/{id}', [EquipmentInspectionController::class, 'show']);
            Route::put('/{id}', [EquipmentInspectionController::class, 'update']);
            
            // Inspection Actions
            Route::post('/{id}/complete', [EquipmentInspectionController::class, 'complete']);
            Route::post('/{id}/fail', [EquipmentInspectionController::class, 'fail']);
            
            // Inspection Schedules
            Route::get('/due-today', [EquipmentInspectionController::class, 'dueToday']);
            Route::get('/overdue', [EquipmentInspectionController::class, 'overdue']);
        });
        
        // Equipment Alerts
        Route::prefix('alerts')->group(function () {
            Route::get('/', [EquipmentAlertController::class, 'index']);
            Route::get('/active', [EquipmentAlertController::class, 'active']);
            Route::post('/{id}/acknowledge', [EquipmentAlertController::class, 'acknowledge']);
            Route::post('/{id}/resolve', [EquipmentAlertController::class, 'resolve']);
            Route::post('/{id}/dismiss', [EquipmentAlertController::class, 'dismiss']);
        });
        
        // Equipment Analytics & Reporting
        Route::prefix('analytics')->group(function () {
            Route::get('/dashboard', [EquipmentRegistryController::class, 'analytics']);
            Route::get('/utilization-trends', [EquipmentUtilizationController::class, 'utilizationTrends']);
            Route::get('/maintenance-trends', [EquipmentMaintenanceController::class, 'maintenanceTrends']);
            Route::get('/cost-analysis', [EquipmentRegistryController::class, 'costAnalysis']);
            Route::get('/lifecycle-analysis', [EquipmentRegistryController::class, 'lifecycleAnalysis']);
        });
    });
    
    // ========================================
    // CROSS-MODULE INTEGRATIONS
    // ========================================
    
    // Labor-Equipment Integration
    Route::prefix('labor-equipment')->group(function () {
        Route::get('/operator-assignments', [LaborEquipmentController::class, 'operatorAssignments']);
        Route::get('/equipment-productivity', [LaborEquipmentController::class, 'equipmentProductivity']);
        Route::get('/operator-performance', [LaborEquipmentController::class, 'operatorPerformance']);
    });
    
    // Returns-Labor Integration
    Route::prefix('returns-labor')->group(function () {
        Route::get('/processing-workload', [ReturnsLaborController::class, 'processingWorkload']);
        Route::get('/refurbishment-tasks', [ReturnsLaborController::class, 'refurbishmentTasks']);
    });
    
    // Equipment-Returns Integration
    Route::prefix('equipment-returns')->group(function () {
        Route::get('/return-equipment-usage', [EquipmentReturnsController::class, 'returnEquipmentUsage']);
        Route::get('/refurbishment-equipment', [EquipmentReturnsController::class, 'refurbishmentEquipment']);
    });
});

// ========================================
// PUBLIC ROUTES (if needed)
// ========================================

Route::prefix('public')->group(function () {
    // Return status lookup (for customers)
    Route::get('/returns/status/{rma_number}', [ReturnAuthorizationController::class, 'publicStatus']);
    
    // Equipment status (for operators)
    Route::get('/equipment/status/{equipment_code}', [EquipmentRegistryController::class, 'publicStatus']);
});