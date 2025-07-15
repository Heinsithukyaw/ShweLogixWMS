<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Integration\ERP\SAP\SAPIntegrationService;
use App\Services\Integration\ERP\Oracle\OracleIntegrationService;
use App\Services\Integration\ERP\Dynamics\DynamicsIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class IntegrationController extends Controller
{
    protected $integrationServices = [];

    public function __construct()
    {
        // Initialize integration services
        $this->integrationServices = [
            'erp' => [
                'sap' => app(SAPIntegrationService::class),
                'oracle' => app(OracleIntegrationService::class),
                'dynamics' => app(DynamicsIntegrationService::class),
            ],
            // Add other integration types as they are implemented
        ];
    }

    /**
     * Get all integration configurations
     */
    public function index(): JsonResponse
    {
        try {
            $configurations = \DB::table('integration_configurations')
                ->select([
                    'id',
                    'integration_type',
                    'provider',
                    'name',
                    'description',
                    'is_active',
                    'is_enabled',
                    'health_status',
                    'last_sync_at',
                    'last_health_check_at',
                    'sync_mode',
                    'created_at',
                    'updated_at'
                ])
                ->orderBy('integration_type')
                ->orderBy('provider')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $configurations,
                'meta' => [
                    'total' => $configurations->count(),
                    'active' => $configurations->where('is_active', true)->count(),
                    'healthy' => $configurations->where('health_status', 'healthy')->count()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve integration configurations',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific integration configuration
     */
    public function show(string $integrationType, string $provider): JsonResponse
    {
        try {
            $configuration = \DB::table('integration_configurations')
                ->where('integration_type', $integrationType)
                ->where('provider', $provider)
                ->first();

            if (!$configuration) {
                return response()->json([
                    'success' => false,
                    'error' => 'Integration configuration not found'
                ], 404);
            }

            // Get integration service status
            $service = $this->getIntegrationService($integrationType, $provider);
            $status = $service ? $service->getStatus() : ['status' => 'unavailable'];

            return response()->json([
                'success' => true,
                'data' => [
                    'configuration' => $configuration,
                    'status' => $status,
                    'metrics' => $service ? $service->getMetrics() : null
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve integration configuration',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test integration connection
     */
    public function testConnection(string $integrationType, string $provider): JsonResponse
    {
        try {
            $service = $this->getIntegrationService($integrationType, $provider);
            
            if (!$service) {
                return response()->json([
                    'success' => false,
                    'error' => 'Integration service not available'
                ], 404);
            }

            $connectionTest = $service->testConnection();
            
            // Update health status
            $this->updateHealthStatus($integrationType, $provider, $connectionTest);

            return response()->json([
                'success' => true,
                'data' => [
                    'connection_status' => $connectionTest ? 'connected' : 'disconnected',
                    'tested_at' => now()->toISOString(),
                    'provider' => $provider,
                    'integration_type' => $integrationType
                ]
            ]);

        } catch (Exception $e) {
            $this->updateHealthStatus($integrationType, $provider, false, $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Connection test failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync data with external system
     */
    public function syncData(Request $request, string $integrationType, string $provider): JsonResponse
    {
        try {
            $request->validate([
                'data_type' => 'required|string',
                'data' => 'required|array',
                'sync_mode' => 'sometimes|string|in:real_time,batch'
            ]);

            $service = $this->getIntegrationService($integrationType, $provider);
            
            if (!$service) {
                return response()->json([
                    'success' => false,
                    'error' => 'Integration service not available'
                ], 404);
            }

            $dataType = $request->input('data_type');
            $data = $request->input('data');
            
            $result = $service->syncData($dataType, $data);

            // Log sync job
            $this->logSyncJob($integrationType, $provider, $dataType, $result);

            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'sync_type' => 'manual',
                    'data_type' => $dataType,
                    'record_count' => count($data),
                    'synced_at' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Data sync failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle webhook from external system
     */
    public function handleWebhook(Request $request, string $integrationType, string $provider): JsonResponse
    {
        try {
            $service = $this->getIntegrationService($integrationType, $provider);
            
            if (!$service) {
                return response()->json([
                    'success' => false,
                    'error' => 'Integration service not available'
                ], 404);
            }

            $payload = $request->all();
            $headers = $request->headers->all();

            // Log webhook receipt
            $webhookId = $this->logWebhook($integrationType, $provider, $payload, $headers);

            $result = $service->handleWebhook($payload);

            // Update webhook status
            $this->updateWebhookStatus($webhookId, $result['success'] ? 'processed' : 'failed', $result);

            return response()->json([
                'success' => true,
                'data' => $result,
                'webhook_id' => $webhookId
            ]);

        } catch (Exception $e) {
            if (isset($webhookId)) {
                $this->updateWebhookStatus($webhookId, 'failed', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get integration logs
     */
    public function getLogs(Request $request, string $integrationType, string $provider): JsonResponse
    {
        try {
            $query = \DB::table('integration_logs')
                ->where('integration_type', $integrationType)
                ->where('provider', $provider);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('operation')) {
                $query->where('operation', $request->input('operation'));
            }

            if ($request->has('start_date')) {
                $query->where('created_at', '>=', $request->input('start_date'));
            }

            if ($request->has('end_date')) {
                $query->where('created_at', '<=', $request->input('end_date'));
            }

            $perPage = $request->input('per_page', 50);
            $page = $request->input('page', 1);

            $logs = $query->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $total = $query->count();

            return response()->json([
                'success' => true,
                'data' => $logs,
                'meta' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve integration logs',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get integration metrics
     */
    public function getMetrics(Request $request, string $integrationType, string $provider): JsonResponse
    {
        try {
            $service = $this->getIntegrationService($integrationType, $provider);
            
            if (!$service) {
                return response()->json([
                    'success' => false,
                    'error' => 'Integration service not available'
                ], 404);
            }

            $metrics = $service->getMetrics();

            // Get additional metrics from database
            $timeframe = $request->input('timeframe', '24h');
            $since = $this->getTimeframeSince($timeframe);

            $dbMetrics = [
                'total_requests' => \DB::table('integration_logs')
                    ->where('integration_type', $integrationType)
                    ->where('provider', $provider)
                    ->where('created_at', '>=', $since)
                    ->count(),
                
                'successful_requests' => \DB::table('integration_logs')
                    ->where('integration_type', $integrationType)
                    ->where('provider', $provider)
                    ->where('status', 'success')
                    ->where('created_at', '>=', $since)
                    ->count(),
                
                'failed_requests' => \DB::table('integration_logs')
                    ->where('integration_type', $integrationType)
                    ->where('provider', $provider)
                    ->where('status', 'error')
                    ->where('created_at', '>=', $since)
                    ->count(),
                
                'average_response_time' => \DB::table('integration_logs')
                    ->where('integration_type', $integrationType)
                    ->where('provider', $provider)
                    ->where('created_at', '>=', $since)
                    ->whereNotNull('execution_time_ms')
                    ->avg('execution_time_ms'),
                
                'sync_jobs_completed' => \DB::table('integration_sync_jobs')
                    ->where('integration_type', $integrationType)
                    ->where('provider', $provider)
                    ->where('status', 'completed')
                    ->where('created_at', '>=', $since)
                    ->count(),
                
                'webhooks_processed' => \DB::table('integration_webhooks')
                    ->where('integration_type', $integrationType)
                    ->where('provider', $provider)
                    ->where('status', 'processed')
                    ->where('created_at', '>=', $since)
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => array_merge($metrics, $dbMetrics),
                'meta' => [
                    'timeframe' => $timeframe,
                    'since' => $since->toISOString(),
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve integration metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get integration dashboard summary
     */
    public function getDashboardSummary(): JsonResponse
    {
        try {
            $summary = [
                'total_integrations' => \DB::table('integration_configurations')->count(),
                'active_integrations' => \DB::table('integration_configurations')->where('is_active', true)->count(),
                'healthy_integrations' => \DB::table('integration_configurations')->where('health_status', 'healthy')->count(),
                'failed_integrations' => \DB::table('integration_configurations')->where('health_status', 'unhealthy')->count(),
                
                'today_requests' => \DB::table('integration_logs')
                    ->whereDate('created_at', today())
                    ->count(),
                
                'today_successful' => \DB::table('integration_logs')
                    ->whereDate('created_at', today())
                    ->where('status', 'success')
                    ->count(),
                
                'today_failed' => \DB::table('integration_logs')
                    ->whereDate('created_at', today())
                    ->where('status', 'error')
                    ->count(),
                
                'active_sync_jobs' => \DB::table('integration_sync_jobs')
                    ->whereIn('status', ['pending', 'running'])
                    ->count(),
                
                'pending_webhooks' => \DB::table('integration_webhooks')
                    ->whereIn('status', ['pending', 'processing'])
                    ->count(),
            ];

            // Calculate success rate
            $summary['success_rate'] = $summary['today_requests'] > 0 
                ? round(($summary['today_successful'] / $summary['today_requests']) * 100, 2)
                : 100;

            // Get integration breakdown
            $integrationBreakdown = \DB::table('integration_configurations')
                ->select('integration_type', \DB::raw('count(*) as count'))
                ->groupBy('integration_type')
                ->get()
                ->keyBy('integration_type')
                ->map(function($item) {
                    return $item->count;
                });

            // Get recent activity
            $recentActivity = \DB::table('integration_logs')
                ->select(['integration_type', 'provider', 'operation', 'status', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'integration_breakdown' => $integrationBreakdown,
                    'recent_activity' => $recentActivity
                ],
                'meta' => [
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve dashboard summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enable/disable integration
     */
    public function toggleIntegration(Request $request, string $integrationType, string $provider): JsonResponse
    {
        try {
            $request->validate([
                'is_active' => 'required|boolean'
            ]);

            $isActive = $request->input('is_active');

            $updated = \DB::table('integration_configurations')
                ->where('integration_type', $integrationType)
                ->where('provider', $provider)
                ->update([
                    'is_active' => $isActive,
                    'updated_at' => now()
                ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'error' => 'Integration configuration not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'integration_type' => $integrationType,
                    'provider' => $provider,
                    'is_active' => $isActive,
                    'updated_at' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to toggle integration',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get integration service instance
     */
    protected function getIntegrationService(string $integrationType, string $provider)
    {
        return $this->integrationServices[$integrationType][$provider] ?? null;
    }

    /**
     * Update health status for integration
     */
    protected function updateHealthStatus(string $integrationType, string $provider, bool $isHealthy, string $errorMessage = null): void
    {
        $healthStatus = $isHealthy ? 'healthy' : 'unhealthy';
        $healthDetails = $isHealthy ? null : ['error' => $errorMessage];

        \DB::table('integration_configurations')
            ->where('integration_type', $integrationType)
            ->where('provider', $provider)
            ->update([
                'health_status' => $healthStatus,
                'health_details' => $healthDetails ? json_encode($healthDetails) : null,
                'last_health_check_at' => now(),
                'updated_at' => now()
            ]);
    }

    /**
     * Log sync job
     */
    protected function logSyncJob(string $integrationType, string $provider, string $dataType, array $result): void
    {
        \DB::table('integration_sync_jobs')->insert([
            'integration_type' => $integrationType,
            'provider' => $provider,
            'sync_type' => 'manual',
            'data_type' => $dataType,
            'direction' => 'outbound',
            'status' => $result['success'] ? 'completed' : 'failed',
            'started_at' => now(),
            'completed_at' => now(),
            'total_records' => $result['processed'] ?? 0,
            'processed_records' => $result['processed'] ?? 0,
            'successful_records' => $result['success'] ? ($result['processed'] ?? 0) : 0,
            'failed_records' => $result['success'] ? 0 : ($result['processed'] ?? 0),
            'triggered_by' => auth()->id() ?? 'system',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Log webhook receipt
     */
    protected function logWebhook(string $integrationType, string $provider, array $payload, array $headers): int
    {
        return \DB::table('integration_webhooks')->insertGetId([
            'integration_type' => $integrationType,
            'provider' => $provider,
            'event_type' => $payload['event_type'] ?? 'unknown',
            'webhook_url' => request()->fullUrl(),
            'payload' => json_encode($payload),
            'headers' => json_encode($headers),
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Update webhook status
     */
    protected function updateWebhookStatus(int $webhookId, string $status, array $result): void
    {
        $updateData = [
            'status' => $status,
            'updated_at' => now()
        ];

        if (!$result['success']) {
            $updateData['error_message'] = $result['error'] ?? 'Unknown error';
        }

        \DB::table('integration_webhooks')
            ->where('id', $webhookId)
            ->update($updateData);
    }

    /**
     * Get timeframe since timestamp
     */
    protected function getTimeframeSince(string $timeframe): \Carbon\Carbon
    {
        switch ($timeframe) {
            case '1h':
                return now()->subHour();
            case '24h':
                return now()->subDay();
            case '7d':
                return now()->subWeek();
            case '30d':
                return now()->subMonth();
            default:
                return now()->subDay();
        }
    }
}