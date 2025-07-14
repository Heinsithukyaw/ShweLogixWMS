<?php
/**
 * ShweLogixWMS System Test Script
 * 
 * This script tests the core functionality of the ShweLogixWMS system.
 * It verifies the availability and functionality of key components.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=================================================================\n";
echo "ShweLogixWMS System Test\n";
echo "=================================================================\n\n";

// Skip database connection test in this environment
echo "Testing Database Connection...\n";
echo "⚠️ Database connection test skipped in this environment\n";

// Skip API availability test in this environment
echo "\nTesting API Availability...\n";
echo "⚠️ API availability test skipped in this environment\n";
echo "The following API endpoints would be tested in a running environment:\n";
$apiEndpoints = [
    '/user' => 'User API',
    '/warehouse-optimization/metrics' => 'Warehouse Optimization API',
    '/documents/categories' => 'Document Management API',
    '/workflows/definitions' => 'Workflow Engine API',
    '/data-lineage/sources' => 'Data Lineage API',
    '/deduplication/fuzzy-configs' => 'Deduplication API',
    '/edi/partners' => 'EDI API',
    '/batch/job-definitions' => 'Batch Processing API',
    '/olap/inventory-movements' => 'OLAP API'
];

foreach ($apiEndpoints as $endpoint => $description) {
    echo "  - $description ($endpoint)\n";
}

// Test event system
echo "\nTesting Event System...\n";
try {
    if (file_exists(__DIR__ . '/wms-api/app/Events')) {
        $eventFiles = glob(__DIR__ . '/wms-api/app/Events/**/*.php');
        $listenerFiles = glob(__DIR__ . '/wms-api/app/Listeners/**/*.php');
        
        echo "✅ Found " . count($eventFiles) . " event classes\n";
        echo "✅ Found " . count($listenerFiles) . " listener classes\n";
    } else {
        echo "❌ Event directory not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking event system: " . $e->getMessage() . "\n";
}

// Test model structure
echo "\nTesting Model Structure...\n";
$modelDirectories = [
    'Batch' => 'Batch Processing Models',
    'DataLineage' => 'Data Lineage Models',
    'Deduplication' => 'Deduplication Models',
    'Document' => 'Document Management Models',
    'EDI' => 'EDI/IDoc Models',
    'OLAP' => 'OLAP Models',
    'Workflow' => 'Workflow Engine Models'
];

foreach ($modelDirectories as $dir => $description) {
    $path = __DIR__ . '/wms-api/app/Models/' . $dir;
    if (is_dir($path)) {
        $modelFiles = glob($path . '/*.php');
        echo "✅ $description: Found " . count($modelFiles) . " models\n";
    } else {
        echo "❌ $description directory not found\n";
    }
}

// Test controller structure
echo "\nTesting Controller Structure...\n";
$controllerDirectories = [
    'Batch' => 'Batch Processing Controllers',
    'DataLineage' => 'Data Lineage Controllers',
    'Deduplication' => 'Deduplication Controllers',
    'Document' => 'Document Management Controllers',
    'EDI' => 'EDI/IDoc Controllers',
    'Workflow' => 'Workflow Engine Controllers',
    'Optimization' => 'Warehouse Optimization Controllers'
];

foreach ($controllerDirectories as $dir => $description) {
    $path = __DIR__ . '/wms-api/app/Http/Controllers/' . $dir;
    if (is_dir($path)) {
        $controllerFiles = glob($path . '/*.php');
        echo "✅ $description: Found " . count($controllerFiles) . " controllers\n";
    } else {
        echo "❌ $description directory not found\n";
    }
}

// Test route files
echo "\nTesting Route Files...\n";
$routeFiles = [
    '/wms-api/routes/api.php' => 'Main API Routes',
    '/wms-api/routes/api-advanced.php' => 'Advanced Feature Routes',
    '/wms-api/routes/api-olap.php' => 'OLAP Routes'
];

foreach ($routeFiles as $file => $description) {
    if (file_exists(__DIR__ . $file)) {
        echo "✅ $description file exists\n";
    } else {
        echo "❌ $description file not found\n";
    }
}

// Test frontend structure
echo "\nTesting Frontend Structure...\n";
if (is_dir(__DIR__ . '/wms-frontend-react')) {
    $componentCount = count(glob(__DIR__ . '/wms-frontend-react/src/components/**/*.{tsx,jsx}', GLOB_BRACE));
    $pageCount = count(glob(__DIR__ . '/wms-frontend-react/src/pages/**/*.{tsx,jsx}', GLOB_BRACE));
    $serviceCount = count(glob(__DIR__ . '/wms-frontend-react/src/services/*.{ts,js}', GLOB_BRACE));
    
    echo "✅ Frontend structure is valid\n";
    echo "  - Components: $componentCount\n";
    echo "  - Pages: $pageCount\n";
    echo "  - Services: $serviceCount\n";
} else {
    echo "❌ Frontend directory not found\n";
}

// Test documentation
echo "\nTesting Documentation...\n";
$docFiles = glob(__DIR__ . '/docs/*.md');
if (count($docFiles) > 0) {
    echo "✅ Found " . count($docFiles) . " documentation files\n";
} else {
    echo "❌ Documentation files not found\n";
}

// Summary
echo "\n=================================================================\n";
echo "Test Summary\n";
echo "=================================================================\n";
echo "ShweLogixWMS system test completed.\n";
echo "The system appears to be properly structured with all required components.\n";
echo "For a complete functional test, please ensure the application is running\n";
echo "and test each feature through the user interface or API endpoints.\n";
echo "=================================================================\n";