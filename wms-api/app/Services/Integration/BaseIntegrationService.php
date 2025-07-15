<?php

namespace App\Services\Integration;

use App\Services\EventLogService;
use App\Services\IdempotencyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

abstract class BaseIntegrationService
{
    protected $config;
    protected $logger;
    protected $eventService;
    protected $idempotencyService;
    protected $integrationName;
    protected $provider;
    protected $retryAttempts = 3;
    protected $retryDelay = 1000; // milliseconds

    public function __construct(
        EventLogService $eventService,
        IdempotencyService $idempotencyService
    ) {
        $this->eventService = $eventService;
        $this->idempotencyService = $idempotencyService;
        $this->logger = Log::channel('integration');
        $this->loadConfiguration();
    }

    /**
     * Load integration-specific configuration
     */
    protected function loadConfiguration()
    {
        $this->config = config("integrations.{$this->integrationName}.{$this->provider}", []);
    }

    /**
     * Check if integration is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    /**
     * Authenticate with external system
     */
    abstract public function authenticate(): bool;

    /**
     * Sync data with external system
     */
    abstract public function syncData(string $dataType, array $data): array;

    /**
     * Handle webhook from external system
     */
    abstract public function handleWebhook(array $payload): array;

    /**
     * Get integration status
     */
    abstract public function getStatus(): array;

    /**
     * Test connection to external system
     */
    abstract public function testConnection(): bool;

    /**
     * Execute API request with retry logic
     */
    protected function executeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryAttempts) {
            try {
                $response = $this->makeHttpRequest($method, $endpoint, $data, $headers);
                
                $this->logRequest($method, $endpoint, $data, $response, 'success');
                
                return [
                    'success' => true,
                    'data' => $response,
                    'status_code' => 200
                ];

            } catch (Exception $e) {
                $lastException = $e;
                $attempt++;
                
                $this->logger->warning("Integration request failed (attempt {$attempt})", [
                    'integration' => $this->integrationName,
                    'provider' => $this->provider,
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage()
                ]);

                if ($attempt < $this->retryAttempts) {
                    usleep($this->retryDelay * 1000 * $attempt); // Exponential backoff
                }
            }
        }

        $this->logRequest($method, $endpoint, $data, null, 'error', $lastException->getMessage());

        return [
            'success' => false,
            'error' => $lastException->getMessage(),
            'status_code' => $lastException->getCode() ?: 500
        ];
    }

    /**
     * Make HTTP request
     */
    protected function makeHttpRequest(string $method, string $endpoint, array $data = [], array $headers = [])
    {
        $http = Http::withHeaders($headers)->timeout(30);

        switch (strtoupper($method)) {
            case 'GET':
                return $http->get($endpoint, $data)->json();
            case 'POST':
                return $http->post($endpoint, $data)->json();
            case 'PUT':
                return $http->put($endpoint, $data)->json();
            case 'DELETE':
                return $http->delete($endpoint, $data)->json();
            default:
                throw new Exception("Unsupported HTTP method: {$method}");
        }
    }

    /**
     * Log integration request
     */
    protected function logRequest(string $method, string $endpoint, array $data, $response, string $status, string $error = null)
    {
        $logData = [
            'integration_type' => $this->integrationName,
            'provider' => $this->provider,
            'operation' => "{$method} {$endpoint}",
            'request_data' => $data,
            'response_data' => $response,
            'status' => $status,
            'error_message' => $error,
            'timestamp' => now()
        ];

        // Store in database
        \DB::table('integration_logs')->insert($logData);

        // Log to file
        $this->logger->info("Integration request logged", $logData);
    }

    /**
     * Process with idempotency protection
     */
    protected function processWithIdempotency(string $operation, array $data, callable $processor)
    {
        $idempotencyKey = $this->generateIdempotencyKey($operation, $data);
        
        return $this->idempotencyService->processWithIdempotency(
            $idempotencyKey,
            $processor,
            3600 // 1 hour TTL
        );
    }

    /**
     * Generate idempotency key
     */
    protected function generateIdempotencyKey(string $operation, array $data): string
    {
        $keyData = [
            'integration' => $this->integrationName,
            'provider' => $this->provider,
            'operation' => $operation,
            'data' => $data
        ];

        return hash('sha256', json_encode($keyData));
    }

    /**
     * Cache data with TTL
     */
    protected function cacheData(string $key, $data, int $ttl = 3600)
    {
        $cacheKey = "integration:{$this->integrationName}:{$this->provider}:{$key}";
        Cache::put($cacheKey, $data, $ttl);
    }

    /**
     * Get cached data
     */
    protected function getCachedData(string $key)
    {
        $cacheKey = "integration:{$this->integrationName}:{$this->provider}:{$key}";
        return Cache::get($cacheKey);
    }

    /**
     * Emit integration event
     */
    protected function emitEvent(string $eventType, array $data)
    {
        $eventData = [
            'integration_type' => $this->integrationName,
            'provider' => $this->provider,
            'event_type' => $eventType,
            'data' => $data,
            'timestamp' => now()
        ];

        $this->eventService->logEvent(
            "integration.{$eventType}",
            $eventData,
            'integration_service'
        );
    }

    /**
     * Validate required configuration
     */
    protected function validateConfiguration(array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                $this->logger->error("Missing required configuration: {$key}", [
                    'integration' => $this->integrationName,
                    'provider' => $this->provider
                ]);
                return false;
            }
        }
        return true;
    }

    /**
     * Transform data using mapping configuration
     */
    protected function transformData(array $data, array $mapping): array
    {
        $transformed = [];
        
        foreach ($mapping as $sourceField => $targetField) {
            if (isset($data[$sourceField])) {
                $transformed[$targetField] = $data[$sourceField];
            }
        }
        
        return $transformed;
    }

    /**
     * Handle integration error
     */
    protected function handleError(Exception $e, string $operation, array $context = [])
    {
        $errorData = [
            'integration' => $this->integrationName,
            'provider' => $this->provider,
            'operation' => $operation,
            'error' => $e->getMessage(),
            'context' => $context,
            'timestamp' => now()
        ];

        $this->logger->error("Integration error occurred", $errorData);
        
        $this->emitEvent('error', $errorData);

        return [
            'success' => false,
            'error' => $e->getMessage(),
            'error_code' => $e->getCode()
        ];
    }

    /**
     * Get integration metrics
     */
    public function getMetrics(): array
    {
        $cacheKey = "metrics:{$this->integrationName}:{$this->provider}";
        
        return Cache::remember($cacheKey, 300, function () {
            return [
                'total_requests' => $this->getTotalRequests(),
                'success_rate' => $this->getSuccessRate(),
                'average_response_time' => $this->getAverageResponseTime(),
                'error_count' => $this->getErrorCount(),
                'last_sync' => $this->getLastSyncTime(),
                'status' => $this->isEnabled() ? 'active' : 'inactive'
            ];
        });
    }

    /**
     * Get total requests count
     */
    protected function getTotalRequests(): int
    {
        return \DB::table('integration_logs')
            ->where('integration_type', $this->integrationName)
            ->where('provider', $this->provider)
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    /**
     * Get success rate percentage
     */
    protected function getSuccessRate(): float
    {
        $total = $this->getTotalRequests();
        if ($total === 0) return 100.0;

        $successful = \DB::table('integration_logs')
            ->where('integration_type', $this->integrationName)
            ->where('provider', $this->provider)
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get average response time
     */
    protected function getAverageResponseTime(): float
    {
        // This would need to be implemented with actual timing data
        return 0.0;
    }

    /**
     * Get error count
     */
    protected function getErrorCount(): int
    {
        return \DB::table('integration_logs')
            ->where('integration_type', $this->integrationName)
            ->where('provider', $this->provider)
            ->where('status', 'error')
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    /**
     * Get last sync time
     */
    protected function getLastSyncTime(): ?string
    {
        $lastSync = \DB::table('integration_configurations')
            ->where('integration_type', $this->integrationName)
            ->where('provider', $this->provider)
            ->value('last_sync_at');

        return $lastSync ? $lastSync->toISOString() : null;
    }
}