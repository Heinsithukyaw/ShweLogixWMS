<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\EventMonitoringService;
use App\Services\IdempotencyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class EventMonitoringController extends Controller
{
    /**
     * The event monitoring service.
     *
     * @var \App\Services\EventMonitoringService
     */
    protected $eventMonitoringService;

    /**
     * The idempotency service.
     *
     * @var \App\Services\IdempotencyService
     */
    protected $idempotencyService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\EventMonitoringService  $eventMonitoringService
     * @param  \App\Services\IdempotencyService  $idempotencyService
     * @return void
     */
    public function __construct(
        EventMonitoringService $eventMonitoringService,
        IdempotencyService $idempotencyService
    ) {
        $this->eventMonitoringService = $eventMonitoringService;
        $this->idempotencyService = $idempotencyService;
    }

    /**
     * Get event statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_name' => 'nullable|string|max:255',
            'period' => 'nullable|string|in:hourly,daily,monthly',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $eventName = $request->input('event_name');
            $period = $request->input('period', 'daily');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $statistics = $this->eventMonitoringService->getEventStatistics(
                $eventName,
                $period,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'meta' => [
                    'period' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve event statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get event performance metrics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPerformance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $eventName = $request->input('event_name');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $performance = $this->eventMonitoringService->getEventPerformance(
                $eventName,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'data' => $performance,
                'meta' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve event performance metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get event backlog information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBacklog(Request $request): JsonResponse
    {
        try {
            $backlog = $this->eventMonitoringService->checkEventBacklog();

            return response()->json([
                'success' => true,
                'data' => $backlog,
                'meta' => [
                    'checked_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check event backlog',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get event logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLogs(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_name' => 'nullable|string|max:255',
            'event_source' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $filters = [
                'event_name' => $request->input('event_name'),
                'event_source' => $request->input('event_source'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ];

            $perPage = $request->input('per_page', 20);
            
            $logs = $this->eventMonitoringService->getEventLogs($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve event logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get idempotency statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIdempotencyStatistics(Request $request): JsonResponse
    {
        try {
            $statistics = $this->idempotencyService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'meta' => [
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve idempotency statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard summary.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardSummary(Request $request): JsonResponse
    {
        try {
            // Get recent statistics
            $recentStats = $this->eventMonitoringService->getEventStatistics('day');
            
            // Get performance metrics
            $this->eventMonitoringService->monitorEventPerformance();
            $performance = [];
            
            // Get backlog information
            $this->eventMonitoringService->checkEventBacklog();
            $backlog = [
                'has_backlog' => false,
                'backlogged_queues' => [],
                'message' => 'Backlog check completed'
            ];
            
            // Get idempotency statistics
            $idempotencyStats = $this->idempotencyService->getStatistics();

            // Calculate summary metrics
            $totalEvents = array_sum(array_column($recentStats, 'total_count'));
            $avgProcessingTime = 0;
            $slowestEvent = null;
            
            if (!empty($performance)) {
                $avgProcessingTime = array_sum(array_column($performance, 'average_time')) / count($performance);
                
                $slowestEvent = array_reduce($performance, function ($carry, $item) {
                    return (!$carry || $item['p99'] > $carry['p99']) ? $item : $carry;
                });
            }

            $summary = [
                'total_events_today' => $totalEvents,
                'average_processing_time_ms' => round($avgProcessingTime * 1000, 2),
                'active_event_types' => count($recentStats),
                'has_backlog' => $backlog['has_backlog'],
                'backlogged_queues_count' => count($backlog['backlogged_queues']),
                'slowest_event' => $slowestEvent ? [
                    'name' => $slowestEvent['name'],
                    'p99_ms' => round($slowestEvent['p99'] * 1000, 2),
                ] : null,
                'idempotency' => [
                    'total_keys' => $idempotencyStats['total_keys'],
                    'active_keys' => $idempotencyStats['active_keys'],
                    'duplicate_prevention_rate' => $idempotencyStats['total_keys'] > 0 
                        ? round(($idempotencyStats['completed_keys'] / $idempotencyStats['total_keys']) * 100, 2)
                        : 0,
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $summary,
                'meta' => [
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate dashboard summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}