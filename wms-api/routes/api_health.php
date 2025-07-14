<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| Health Check API Routes
|--------------------------------------------------------------------------
*/

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