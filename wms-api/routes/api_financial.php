<?php

use App\Http\Controllers\Api\Financial\BillingRateController;
use App\Http\Controllers\Api\Financial\BudgetVsActualController;
use App\Http\Controllers\Api\Financial\CostCategoryController;
use App\Http\Controllers\Api\Financial\HandlingCostController;
use App\Http\Controllers\Api\Financial\HandlingRevenueRateController;
use App\Http\Controllers\Api\Financial\OverheadCostController;
use App\Http\Controllers\Api\Financial\RevenueCategoryController;
use App\Http\Controllers\Api\Financial\RevenueReportController;
use App\Http\Controllers\Api\Financial\RevenueTransactionController;
use App\Http\Controllers\Api\Financial\StorageCostController;
use App\Http\Controllers\Api\Financial\StorageRevenueRateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Financial API Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'financial'], function () {
    // Cost Management Routes
    Route::apiResource('cost-categories', CostCategoryController::class);
    Route::apiResource('overhead-costs', OverheadCostController::class);
    Route::apiResource('storage-costs', StorageCostController::class);
    Route::apiResource('handling-costs', HandlingCostController::class);
    Route::apiResource('budget-vs-actual', BudgetVsActualController::class);

    // Revenue Management Routes
    Route::apiResource('revenue-categories', RevenueCategoryController::class);
    Route::apiResource('storage-revenue-rates', StorageRevenueRateController::class);
    Route::apiResource('handling-revenue-rates', HandlingRevenueRateController::class);
    Route::apiResource('revenue-transactions', RevenueTransactionController::class);
    Route::apiResource('billing-rates', BillingRateController::class);
    Route::apiResource('revenue-reports', RevenueReportController::class);
});