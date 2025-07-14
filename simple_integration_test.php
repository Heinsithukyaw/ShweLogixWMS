<?php

/**
 * ShweLogixWMS Simple Integration System Test Suite
 * 
 * This script tests all integration components without Laravel dependencies
 */

class SimpleIntegrationTestSuite
{
    private $results = [];
    private $baseUrl = 'http://localhost:12000';

    public function __construct()
    {
        echo "🚀 ShweLogixWMS Integration System Test Suite\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
    }

    public function runAllTests()
    {
        $this->testDatabaseConnection();
        $this->testRedisConnection();
        $this->testHealthEndpoints();
        $this->testIntegrationStatus();
        $this->testQueueSystem();
        $this->testSchedulerSystem();
        $this->testIntegrationTables();
        $this->testEnvironmentConfiguration();
        $this->testLaravelServer();
        
        $this->generateReport();
    }

    private function testDatabaseConnection()
    {
        echo "📊 Testing Database Connection...\n";
        
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=shwelogix_wms', 'root', 'password123');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'shwelogix_wms'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->results['database'] = [
                'status' => 'PASS',
                'message' => "Connected successfully. Found {$result['table_count']} tables.",
                'details' => $result
            ];
            echo "   ✅ Database connection successful\n";
        } catch (Exception $e) {
            $this->results['database'] = [
                'status' => 'FAIL',
                'message' => $e->getMessage(),
                'details' => null
            ];
            echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
        }
    }

    private function testRedisConnection()
    {
        echo "🔴 Testing Redis Connection...\n";
        
        try {
            // Test via command line
            $output = shell_exec('redis-cli ping 2>/dev/null');
            if (trim($output) === 'PONG') {
                $this->results['redis'] = [
                    'status' => 'PASS',
                    'message' => 'Redis connection successful',
                    'details' => ['method' => 'command_line']
                ];
                echo "   ✅ Redis connection successful\n";
            } else {
                throw new Exception('Redis not responding to ping');
            }
        } catch (Exception $e) {
            $this->results['redis'] = [
                'status' => 'FAIL',
                'message' => $e->getMessage(),
                'details' => null
            ];
            echo "   ❌ Redis connection failed: " . $e->getMessage() . "\n";
        }
    }

    private function testHealthEndpoints()
    {
        echo "🏥 Testing Health Endpoints...\n";
        
        $endpoints = [
            '/api/admin/v1/health',
            '/api/admin/v1/integration/status'
        ];

        foreach ($endpoints as $endpoint) {
            $url = $this->baseUrl . $endpoint;
            $response = $this->makeHttpRequest($url);
            
            if ($response && isset($response['status']) && $response['status'] === 'ok') {
                echo "   ✅ $endpoint - OK\n";
                $this->results['health_' . str_replace('/', '_', $endpoint)] = [
                    'status' => 'PASS',
                    'message' => 'Endpoint responding correctly',
                    'details' => $response
                ];
            } else {
                echo "   ❌ $endpoint - FAIL\n";
                $this->results['health_' . str_replace('/', '_', $endpoint)] = [
                    'status' => 'FAIL',
                    'message' => 'Endpoint not responding or invalid response',
                    'details' => $response
                ];
            }
        }
    }

    private function testIntegrationStatus()
    {
        echo "🔗 Testing Integration Status...\n";
        
        $url = $this->baseUrl . '/api/admin/v1/integration/status';
        $response = $this->makeHttpRequest($url);
        
        if ($response && isset($response['integrations'])) {
            $totalIntegrations = count($response['integrations']);
            $enabledIntegrations = count(array_filter($response['integrations']));
            
            echo "   📊 Total integrations configured: $totalIntegrations\n";
            echo "   ✅ Enabled integrations: $enabledIntegrations\n";
            echo "   📈 Enablement percentage: " . $response['summary']['percentage'] . "%\n";
            
            $this->results['integration_status'] = [
                'status' => 'PASS',
                'message' => "Integration system operational",
                'details' => $response['summary']
            ];
        } else {
            echo "   ❌ Integration status endpoint failed\n";
            $this->results['integration_status'] = [
                'status' => 'FAIL',
                'message' => 'Integration status endpoint not responding',
                'details' => null
            ];
        }
    }

    private function testQueueSystem()
    {
        echo "⚡ Testing Queue System...\n";
        
        try {
            // Check Redis keys for Laravel queues
            $output = shell_exec('redis-cli keys "laravel_database_*" 2>/dev/null | wc -l');
            $redisKeys = intval(trim($output));
            
            echo "   📊 Queue keys found: $redisKeys\n";
            
            // Check if jobs table exists
            $pdo = new PDO('mysql:host=localhost;dbname=shwelogix_wms', 'root', 'password123');
            $stmt = $pdo->query("SHOW TABLES LIKE 'jobs'");
            
            if ($stmt->rowCount() > 0) {
                echo "   ✅ Jobs table exists\n";
                
                $stmt = $pdo->query("SELECT COUNT(*) as job_count FROM jobs");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "   📊 Jobs in queue: " . $result['job_count'] . "\n";
                
                // Check if queue worker process is running
                $workerProcess = shell_exec('pgrep -f "queue:work" | wc -l');
                $workerCount = intval(trim($workerProcess));
                echo "   📊 Queue workers running: $workerCount\n";
                
                $this->results['queue_system'] = [
                    'status' => 'PASS',
                    'message' => 'Queue system operational',
                    'details' => [
                        'redis_keys' => $redisKeys,
                        'jobs_in_queue' => $result['job_count'],
                        'workers_running' => $workerCount
                    ]
                ];
            } else {
                echo "   ❌ Jobs table not found\n";
                $this->results['queue_system'] = [
                    'status' => 'FAIL',
                    'message' => 'Jobs table not found',
                    'details' => null
                ];
            }
            
        } catch (Exception $e) {
            echo "   ❌ Queue system test failed: " . $e->getMessage() . "\n";
            $this->results['queue_system'] = [
                'status' => 'FAIL',
                'message' => $e->getMessage(),
                'details' => null
            ];
        }
    }

    private function testSchedulerSystem()
    {
        echo "⏰ Testing Scheduler System...\n";
        
        // Check if scheduler process is running
        $schedulerProcess = shell_exec('pgrep -f "scheduler.sh" | wc -l');
        $schedulerCount = intval(trim($schedulerProcess));
        echo "   📊 Scheduler processes running: $schedulerCount\n";
        
        // Check if scheduler log file exists and has recent entries
        $logFile = '/workspace/ShweLogixWMS/wms-api/scheduler.log';
        
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            $lines = explode("\n", trim($logContent));
            $recentLines = array_slice($lines, -5); // Get last 5 lines
            
            echo "   ✅ Scheduler log file exists\n";
            echo "   📊 Recent log entries: " . count($recentLines) . "\n";
            
            $this->results['scheduler_system'] = [
                'status' => 'PASS',
                'message' => 'Scheduler system operational',
                'details' => [
                    'log_file_exists' => true,
                    'recent_entries' => count($recentLines),
                    'processes_running' => $schedulerCount
                ]
            ];
        } else {
            echo "   ⚠️  Scheduler log file not found (may not have run yet)\n";
            $this->results['scheduler_system'] = [
                'status' => 'WARNING',
                'message' => 'Scheduler log file not found',
                'details' => [
                    'log_file_exists' => false,
                    'processes_running' => $schedulerCount
                ]
            ];
        }
    }

    private function testIntegrationTables()
    {
        echo "🗄️  Testing Integration Tables...\n";
        
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=shwelogix_wms', 'root', 'password123');
            
            $integrationTables = [
                'integration_configurations',
                'integration_logs',
                'integration_sync_jobs',
                'integration_webhooks',
                'integration_data_mappings'
            ];
            
            $allTablesExist = true;
            foreach ($integrationTables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() === 0) {
                    $allTablesExist = false;
                    echo "   ❌ Table $table not found\n";
                } else {
                    echo "   ✅ Table $table exists\n";
                    
                    // Get row count
                    $stmt = $pdo->query("SELECT COUNT(*) as row_count FROM $table");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "      📊 Rows: " . $result['row_count'] . "\n";
                }
            }
            
            $this->results['integration_tables'] = [
                'status' => $allTablesExist ? 'PASS' : 'FAIL',
                'message' => $allTablesExist ? 'All integration tables exist' : 'Some integration tables missing',
                'details' => ['tables_checked' => count($integrationTables)]
            ];
            
        } catch (Exception $e) {
            echo "   ❌ Integration tables test failed: " . $e->getMessage() . "\n";
            $this->results['integration_tables'] = [
                'status' => 'FAIL',
                'message' => $e->getMessage(),
                'details' => null
            ];
        }
    }

    private function testEnvironmentConfiguration()
    {
        echo "⚙️  Testing Environment Configuration...\n";
        
        $envFile = '/workspace/ShweLogixWMS/wms-api/.env';
        
        if (!file_exists($envFile)) {
            echo "   ❌ .env file not found\n";
            $this->results['environment'] = [
                'status' => 'FAIL',
                'message' => '.env file not found',
                'details' => null
            ];
            return;
        }
        
        $envContent = file_get_contents($envFile);
        
        $requiredVars = [
            'APP_KEY',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_DATABASE',
            'DB_USERNAME',
            'REDIS_HOST'
        ];
        
        $missingVars = [];
        $configuredVars = 0;
        
        foreach ($requiredVars as $var) {
            if (strpos($envContent, $var . '=') !== false) {
                $configuredVars++;
                echo "   ✅ $var configured\n";
            } else {
                $missingVars[] = $var;
                echo "   ❌ $var missing\n";
            }
        }
        
        // Count integration-specific configurations
        $integrationVars = preg_match_all('/^[A-Z_]+_INTEGRATION_ENABLED=/m', $envContent);
        echo "   📊 Integration configurations found: $integrationVars\n";
        
        $this->results['environment'] = [
            'status' => empty($missingVars) ? 'PASS' : 'FAIL',
            'message' => empty($missingVars) ? 'All required environment variables configured' : 'Missing required environment variables',
            'details' => [
                'configured_vars' => $configuredVars,
                'missing_vars' => $missingVars,
                'integration_configs' => $integrationVars
            ]
        ];
    }

    private function testLaravelServer()
    {
        echo "🌐 Testing Laravel Server...\n";
        
        // Check if Laravel server process is running
        $serverProcess = shell_exec('pgrep -f "php artisan serve" | wc -l');
        $serverCount = intval(trim($serverProcess));
        echo "   📊 Laravel server processes running: $serverCount\n";
        
        // Test basic Laravel response
        $response = $this->makeHttpRequest($this->baseUrl);
        
        if ($serverCount > 0) {
            echo "   ✅ Laravel server is running\n";
            $this->results['laravel_server'] = [
                'status' => 'PASS',
                'message' => 'Laravel server operational',
                'details' => [
                    'processes_running' => $serverCount,
                    'base_url_accessible' => $response !== null
                ]
            ];
        } else {
            echo "   ❌ Laravel server not running\n";
            $this->results['laravel_server'] = [
                'status' => 'FAIL',
                'message' => 'Laravel server not running',
                'details' => ['processes_running' => $serverCount]
            ];
        }
    }

    private function makeHttpRequest($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        
        return null;
    }

    private function generateReport()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📋 INTEGRATION SYSTEM TEST REPORT\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results, function($result) {
            return $result['status'] === 'PASS';
        }));
        $failedTests = count(array_filter($this->results, function($result) {
            return $result['status'] === 'FAIL';
        }));
        $warningTests = count(array_filter($this->results, function($result) {
            return $result['status'] === 'WARNING';
        }));
        
        echo "📊 SUMMARY:\n";
        echo "   Total Tests: $totalTests\n";
        echo "   ✅ Passed: $passedTests\n";
        echo "   ❌ Failed: $failedTests\n";
        echo "   ⚠️  Warnings: $warningTests\n";
        echo "   📈 Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";
        
        echo "📋 DETAILED RESULTS:\n";
        foreach ($this->results as $testName => $result) {
            $icon = $result['status'] === 'PASS' ? '✅' : ($result['status'] === 'WARNING' ? '⚠️' : '❌');
            echo "   $icon " . strtoupper(str_replace('_', ' ', $testName)) . ": " . $result['status'] . "\n";
            echo "      " . $result['message'] . "\n";
            if ($result['details']) {
                echo "      Details: " . json_encode($result['details'], JSON_PRETTY_PRINT) . "\n";
            }
            echo "\n";
        }
        
        echo "🎯 PRODUCTION READINESS:\n";
        if ($failedTests === 0) {
            echo "   🟢 READY - All critical systems operational\n";
        } elseif ($failedTests <= 2) {
            echo "   🟡 CAUTION - Minor issues detected, review required\n";
        } else {
            echo "   🔴 NOT READY - Critical issues must be resolved\n";
        }
        
        echo "\n📅 Test completed at: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 60) . "\n";
    }
}

// Run the test suite
$testSuite = new SimpleIntegrationTestSuite();
$testSuite->runAllTests();