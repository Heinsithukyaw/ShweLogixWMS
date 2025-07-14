<?php

namespace App\Services;

use App\Events\Notification\SystemAlertEvent;
use App\Models\EventLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventMonitoringService
{
    /**
     * Get event statistics for the dashboard.
     *
     * @param string $timeframe
     * @return array
     */
    public function getEventStatistics($timeframe = 'day')
    {
        try {
            $dateColumn = 'created_at';
            $groupFormat = '%Y-%m-%d %H:00:00';
            
            switch ($timeframe) {
                case 'hour':
                    $dateColumn = DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00')");
                    $groupFormat = '%Y-%m-%d %H:%i:00';
                    $startDate = now()->subHour();
                    break;
                case 'day':
                    $dateColumn = DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')");
                    $groupFormat = '%Y-%m-%d %H:00:00';
                    $startDate = now()->subDay();
                    break;
                case 'week':
                    $dateColumn = DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')");
                    $groupFormat = '%Y-%m-%d';
                    $startDate = now()->subWeek();
                    break;
                case 'month':
                    $dateColumn = DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')");
                    $groupFormat = '%Y-%m-%d';
                    $startDate = now()->subMonth();
                    break;
                default:
                    $dateColumn = DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')");
                    $groupFormat = '%Y-%m-%d %H:00:00';
                    $startDate = now()->subDay();
                    break;
            }
            
            // Get event counts by type
            $eventsByType = EventLog::select('event_name as event_type', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', $startDate)
                ->groupBy('event_name')
                ->orderBy('count', 'desc')
                ->get()
                ->toArray();
            
            // Get event counts by time
            $eventsByTime = EventLog::select(
                    $dateColumn,
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', $startDate)
                ->groupBy($dateColumn)
                ->orderBy($dateColumn)
                ->get()
                ->toArray();
            
            // Get error counts (assuming we don't have status column, we'll return 0 for now)
            $errorCount = 0;
            
            // Get total event count
            $totalCount = EventLog::where('created_at', '>=', $startDate)
                ->count();
            
            // Get top error types (assuming we don't have status column, we'll return empty for now)
            $topErrors = [];
            
            return [
                'events_by_type' => $eventsByType,
                'events_by_time' => $eventsByTime,
                'error_count' => $errorCount,
                'total_count' => $totalCount,
                'error_rate' => $totalCount > 0 ? ($errorCount / $totalCount) * 100 : 0,
                'top_errors' => $topErrors,
                'timeframe' => $timeframe,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get event statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'events_by_type' => [],
                'events_by_time' => [],
                'error_count' => 0,
                'total_count' => 0,
                'error_rate' => 0,
                'top_errors' => [],
                'timeframe' => $timeframe,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Monitor event processing performance.
     *
     * @return void
     */
    public function monitorEventPerformance()
    {
        try {
            // Calculate average processing time for events in the last hour
            $lastHour = now()->subHour();
            
            $avgProcessingTime = EventLog::where('created_at', '>=', $lastHour)
                ->whereNotNull('processing_time')
                ->avg('processing_time');
            
            // Get the 95th percentile processing time
            $processingTimes = EventLog::where('created_at', '>=', $lastHour)
                ->whereNotNull('processing_time')
                ->orderBy('processing_time')
                ->pluck('processing_time')
                ->toArray();
            
            $p95ProcessingTime = $this->calculatePercentile($processingTimes, 95);
            
            // Check if processing times are above thresholds
            $avgThreshold = 500; // 500ms
            $p95Threshold = 2000; // 2000ms
            
            if ($avgProcessingTime > $avgThreshold) {
                // Dispatch a warning alert
                event(SystemAlertEvent::warning(
                    "Average event processing time ({$avgProcessingTime}ms) exceeds threshold ({$avgThreshold}ms)",
                    'event_performance',
                    'event_monitoring',
                    [
                        'avg_processing_time' => $avgProcessingTime,
                        'threshold' => $avgThreshold,
                    ]
                ));
            }
            
            if ($p95ProcessingTime > $p95Threshold) {
                // Dispatch a warning alert
                event(SystemAlertEvent::warning(
                    "95th percentile event processing time ({$p95ProcessingTime}ms) exceeds threshold ({$p95Threshold}ms)",
                    'event_performance',
                    'event_monitoring',
                    [
                        'p95_processing_time' => $p95ProcessingTime,
                        'threshold' => $p95Threshold,
                    ]
                ));
            }
            
            // Check for event processing failures
            $failureCount = EventLog::where('created_at', '>=', $lastHour)
                ->where('status', 'error')
                ->count();
            
            $totalCount = EventLog::where('created_at', '>=', $lastHour)
                ->count();
            
            $failureRate = ($totalCount > 0) ? ($failureCount / $totalCount) * 100 : 0;
            
            $failureThreshold = 5; // 5%
            
            if ($failureRate > $failureThreshold) {
                // Dispatch an error alert
                event(SystemAlertEvent::error(
                    "Event processing failure rate ({$failureRate}%) exceeds threshold ({$failureThreshold}%)",
                    'event_failures',
                    'event_monitoring',
                    [
                        'failure_rate' => $failureRate,
                        'failure_count' => $failureCount,
                        'total_count' => $totalCount,
                        'threshold' => $failureThreshold,
                    ]
                ));
            }
            
            Log::info('Event performance monitoring completed', [
                'avg_processing_time' => $avgProcessingTime,
                'p95_processing_time' => $p95ProcessingTime,
                'failure_rate' => $failureRate,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to monitor event performance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Calculate the percentile value from an array.
     *
     * @param array $array
     * @param int $percentile
     * @return float|null
     */
    protected function calculatePercentile(array $array, $percentile)
    {
        if (empty($array)) {
            return null;
        }
        
        sort($array);
        $index = ceil(($percentile / 100) * count($array)) - 1;
        return $array[$index] ?? end($array);
    }

    /**
     * Check for event processing backlogs.
     *
     * @return void
     */
    public function checkEventBacklog()
    {
        try {
            // Get the count of events that have been in the queue for too long
            $backlogThreshold = now()->subMinutes(15);
            
            $backlogCount = DB::table('jobs')
                ->where('queue', 'events')
                ->where('created_at', '<', $backlogThreshold)
                ->count();
            
            $backlogCountThreshold = 100;
            
            if ($backlogCount > $backlogCountThreshold) {
                // Dispatch a warning alert
                event(SystemAlertEvent::warning(
                    "Event processing backlog detected ({$backlogCount} events older than 15 minutes)",
                    'event_backlog',
                    'event_monitoring',
                    [
                        'backlog_count' => $backlogCount,
                        'threshold' => $backlogCountThreshold,
                        'age_threshold' => '15 minutes',
                    ]
                ));
            }
            
            // Check for very old events (potential stuck jobs)
            $stuckThreshold = now()->subHour();
            
            $stuckCount = DB::table('jobs')
                ->where('queue', 'events')
                ->where('created_at', '<', $stuckThreshold)
                ->count();
            
            $stuckCountThreshold = 10;
            
            if ($stuckCount > $stuckCountThreshold) {
                // Dispatch an error alert
                event(SystemAlertEvent::error(
                    "Stuck event jobs detected ({$stuckCount} events older than 1 hour)",
                    'stuck_events',
                    'event_monitoring',
                    [
                        'stuck_count' => $stuckCount,
                        'threshold' => $stuckCountThreshold,
                        'age_threshold' => '1 hour',
                    ]
                ));
            }
            
            Log::info('Event backlog check completed', [
                'backlog_count' => $backlogCount,
                'stuck_count' => $stuckCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check event backlog', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}