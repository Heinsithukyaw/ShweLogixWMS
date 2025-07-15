<?php

namespace App\Http\Controllers\Api\SpaceUtilization;

use App\Http\Controllers\Controller;
use App\Models\SpaceUtilization\SpaceUtilizationSnapshot;
use App\Models\SpaceUtilization\WarehouseZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class SpaceUtilizationController extends Controller
{
    /**
     * Get overall space utilization overview
     */
    public function overview(Request $request): JsonResponse
    {
        $timeframe = $request->get('timeframe', '24h');
        $zoneIds = $request->get('zone_ids', []);

        $startTime = $this->getStartTime($timeframe);
        
        $query = SpaceUtilizationSnapshot::where('snapshot_time', '>=', $startTime);
        
        if (!empty($zoneIds)) {
            $query->whereIn('zone_id', $zoneIds);
        }

        $snapshots = $query->with('zone')->get();

        $overview = [
            'total_zones' => $snapshots->pluck('zone_id')->unique()->count(),
            'average_utilization' => $snapshots->avg('utilization_percentage'),
            'total_occupied_area' => $snapshots->sum('occupied_area'),
            'total_occupied_volume' => $snapshots->sum('occupied_volume'),
            'total_occupied_locations' => $snapshots->sum('occupied_locations'),
            'total_available_locations' => $snapshots->sum('total_locations') - $snapshots->sum('occupied_locations'),
            'utilization_by_zone' => $this->getUtilizationByZone($snapshots),
            'utilization_trend' => $this->getUtilizationTrend($snapshots, $timeframe),
            'capacity_alerts' => $this->getCapacityAlerts($snapshots)
        ];

        return response()->json([
            'success' => true,
            'data' => $overview,
            'message' => 'Space utilization overview retrieved successfully'
        ]);
    }

    /**
     * Get detailed utilization snapshots
     */
    public function snapshots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => 'nullable|exists:warehouse_zones,id',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'interval' => 'nullable|in:hour,day,week',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = SpaceUtilizationSnapshot::with('zone');

        if (isset($validated['zone_id'])) {
            $query->where('zone_id', $validated['zone_id']);
        }

        if (isset($validated['start_time'])) {
            $query->where('snapshot_time', '>=', $validated['start_time']);
        }

        if (isset($validated['end_time'])) {
            $query->where('snapshot_time', '<=', $validated['end_time']);
        }

        $query->orderBy('snapshot_time', 'desc');

        $snapshots = $request->has('per_page') 
            ? $query->paginate($validated['per_page'] ?? 15)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $snapshots,
            'message' => 'Utilization snapshots retrieved successfully'
        ]);
    }

    /**
     * Create a new utilization snapshot
     */
    public function createSnapshot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => 'required|exists:warehouse_zones,id',
            'occupied_area' => 'required|numeric|min:0',
            'occupied_volume' => 'required|numeric|min:0',
            'occupied_locations' => 'required|integer|min:0',
            'total_locations' => 'required|integer|min:0',
            'item_count' => 'nullable|integer|min:0',
            'weight_total' => 'nullable|numeric|min:0',
            'utilization_by_category' => 'nullable|array'
        ]);

        $zone = WarehouseZone::find($validated['zone_id']);
        
        $validated['utilization_percentage'] = ($validated['occupied_locations'] / $validated['total_locations']) * 100;
        $validated['density_per_sqm'] = $validated['occupied_locations'] / $zone->usable_area;
        $validated['density_per_cbm'] = $validated['occupied_locations'] / $zone->usable_volume;
        $validated['snapshot_time'] = now();

        $snapshot = SpaceUtilizationSnapshot::create($validated);
        $snapshot->load('zone');

        return response()->json([
            'success' => true,
            'data' => $snapshot,
            'message' => 'Utilization snapshot created successfully'
        ], 201);
    }

    /**
     * Get utilization analytics for a specific zone
     */
    public function zoneAnalytics(WarehouseZone $zone, Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        
        $snapshots = $zone->utilizationSnapshots()
            ->where('snapshot_time', '>=', now()->subDays($days))
            ->orderBy('snapshot_time')
            ->get();

        $analytics = [
            'zone_info' => [
                'id' => $zone->id,
                'name' => $zone->name,
                'code' => $zone->code,
                'type' => $zone->type,
                'total_area' => $zone->total_area,
                'usable_area' => $zone->usable_area,
                'max_capacity' => $zone->max_capacity
            ],
            'current_utilization' => $snapshots->last()?->utilization_percentage ?? 0,
            'average_utilization' => $snapshots->avg('utilization_percentage'),
            'peak_utilization' => $snapshots->max('utilization_percentage'),
            'lowest_utilization' => $snapshots->min('utilization_percentage'),
            'utilization_trend' => $this->calculateTrend($snapshots),
            'density_metrics' => [
                'avg_density_per_sqm' => $snapshots->avg('density_per_sqm'),
                'avg_density_per_cbm' => $snapshots->avg('density_per_cbm'),
                'peak_density_per_sqm' => $snapshots->max('density_per_sqm')
            ],
            'category_breakdown' => $this->getCategoryBreakdown($snapshots),
            'hourly_patterns' => $this->getHourlyPatterns($snapshots),
            'recommendations' => $this->generateRecommendations($zone, $snapshots)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Zone utilization analytics retrieved successfully'
        ]);
    }

    /**
     * Get utilization comparison between zones
     */
    public function zoneComparison(Request $request): JsonResponse
    {
        $zoneIds = $request->get('zone_ids', []);
        $days = $request->get('days', 7);

        $query = WarehouseZone::with(['utilizationSnapshots' => function($q) use ($days) {
            $q->where('snapshot_time', '>=', now()->subDays($days));
        }]);

        if (!empty($zoneIds)) {
            $query->whereIn('id', $zoneIds);
        }

        $zones = $query->get();

        $comparison = $zones->map(function($zone) {
            $snapshots = $zone->utilizationSnapshots;
            
            return [
                'zone_id' => $zone->id,
                'zone_name' => $zone->name,
                'zone_code' => $zone->code,
                'zone_type' => $zone->type,
                'current_utilization' => $snapshots->last()?->utilization_percentage ?? 0,
                'average_utilization' => $snapshots->avg('utilization_percentage'),
                'peak_utilization' => $snapshots->max('utilization_percentage'),
                'utilization_variance' => $this->calculateVariance($snapshots),
                'efficiency_score' => $this->calculateEfficiencyScore($zone, $snapshots),
                'capacity_status' => $this->getCapacityStatus($zone, $snapshots->last())
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $comparison,
            'message' => 'Zone utilization comparison retrieved successfully'
        ]);
    }

    /**
     * Get real-time utilization dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        $zones = WarehouseZone::with(['utilizationSnapshots' => function($q) {
            $q->latest()->limit(1);
        }, 'aisles'])->get();

        $dashboard = [
            'summary' => [
                'total_zones' => $zones->count(),
                'active_zones' => $zones->where('status', 'active')->count(),
                'average_utilization' => $zones->avg('current_utilization'),
                'total_capacity' => $zones->sum('max_capacity'),
                'total_occupied' => $zones->sum(function($zone) {
                    return $zone->utilizationSnapshots->first()?->occupied_locations ?? 0;
                })
            ],
            'zone_status' => $zones->map(function($zone) {
                $latestSnapshot = $zone->utilizationSnapshots->first();
                return [
                    'zone_id' => $zone->id,
                    'zone_name' => $zone->name,
                    'zone_type' => $zone->type,
                    'current_utilization' => $latestSnapshot?->utilization_percentage ?? 0,
                    'status' => $zone->status,
                    'capacity_status' => $this->getCapacityStatus($zone, $latestSnapshot),
                    'last_updated' => $latestSnapshot?->snapshot_time
                ];
            }),
            'alerts' => $this->getCurrentAlerts($zones),
            'trends' => $this->getRecentTrends()
        ];

        return response()->json([
            'success' => true,
            'data' => $dashboard,
            'message' => 'Utilization dashboard data retrieved successfully'
        ]);
    }

    /**
     * Generate utilization report
     */
    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'exists:warehouse_zones,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'format' => 'nullable|in:summary,detailed',
            'include_trends' => 'nullable|boolean',
            'include_recommendations' => 'nullable|boolean'
        ]);

        $query = SpaceUtilizationSnapshot::with('zone')
            ->whereBetween('snapshot_time', [$validated['start_date'], $validated['end_date']]);

        if (isset($validated['zone_ids'])) {
            $query->whereIn('zone_id', $validated['zone_ids']);
        }

        $snapshots = $query->get();

        $report = [
            'report_period' => [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date']
            ],
            'summary' => $this->generateReportSummary($snapshots),
            'zone_performance' => $this->generateZonePerformance($snapshots),
        ];

        if ($validated['include_trends'] ?? false) {
            $report['trends'] = $this->generateTrendAnalysis($snapshots);
        }

        if ($validated['include_recommendations'] ?? false) {
            $report['recommendations'] = $this->generateReportRecommendations($snapshots);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
            'message' => 'Utilization report generated successfully'
        ]);
    }

    // Private helper methods

    private function getStartTime(string $timeframe): Carbon
    {
        return match($timeframe) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay()
        };
    }

    private function getUtilizationByZone($snapshots)
    {
        return $snapshots->groupBy('zone_id')->map(function($zoneSnapshots) {
            $zone = $zoneSnapshots->first()->zone;
            return [
                'zone_id' => $zone->id,
                'zone_name' => $zone->name,
                'zone_type' => $zone->type,
                'average_utilization' => $zoneSnapshots->avg('utilization_percentage'),
                'current_utilization' => $zoneSnapshots->last()->utilization_percentage
            ];
        })->values();
    }

    private function getUtilizationTrend($snapshots, $timeframe)
    {
        $groupBy = match($timeframe) {
            '1h', '24h' => 'H',
            '7d' => 'Y-m-d',
            '30d' => 'Y-m-d',
            default => 'H'
        };

        return $snapshots->groupBy(function($snapshot) use ($groupBy) {
            return $snapshot->snapshot_time->format($groupBy);
        })->map(function($group, $key) {
            return [
                'time' => $key,
                'average_utilization' => $group->avg('utilization_percentage'),
                'total_occupied_locations' => $group->sum('occupied_locations')
            ];
        })->values();
    }

    private function getCapacityAlerts($snapshots)
    {
        return $snapshots->filter(function($snapshot) {
            return $snapshot->utilization_percentage > 90;
        })->map(function($snapshot) {
            return [
                'zone_id' => $snapshot->zone_id,
                'zone_name' => $snapshot->zone->name,
                'utilization' => $snapshot->utilization_percentage,
                'alert_level' => $snapshot->utilization_percentage > 95 ? 'critical' : 'warning',
                'timestamp' => $snapshot->snapshot_time
            ];
        })->values();
    }

    private function calculateTrend($snapshots)
    {
        if ($snapshots->count() < 2) return 'stable';
        
        $first = $snapshots->first()->utilization_percentage;
        $last = $snapshots->last()->utilization_percentage;
        $change = $last - $first;
        
        if ($change > 5) return 'increasing';
        if ($change < -5) return 'decreasing';
        return 'stable';
    }

    private function getCategoryBreakdown($snapshots)
    {
        $categories = [];
        foreach ($snapshots as $snapshot) {
            if ($snapshot->utilization_by_category) {
                foreach ($snapshot->utilization_by_category as $category => $value) {
                    $categories[$category][] = $value;
                }
            }
        }

        return collect($categories)->map(function($values, $category) {
            return [
                'category' => $category,
                'average_utilization' => collect($values)->avg()
            ];
        })->values();
    }

    private function getHourlyPatterns($snapshots)
    {
        return $snapshots->groupBy(function($snapshot) {
            return $snapshot->snapshot_time->format('H');
        })->map(function($hourSnapshots, $hour) {
            return [
                'hour' => (int)$hour,
                'average_utilization' => $hourSnapshots->avg('utilization_percentage'),
                'peak_utilization' => $hourSnapshots->max('utilization_percentage')
            ];
        })->sortBy('hour')->values();
    }

    private function generateRecommendations($zone, $snapshots)
    {
        $recommendations = [];
        $avgUtilization = $snapshots->avg('utilization_percentage');

        if ($avgUtilization > 90) {
            $recommendations[] = [
                'type' => 'capacity_expansion',
                'priority' => 'high',
                'message' => 'Zone is consistently over 90% utilized. Consider expanding capacity or optimizing layout.'
            ];
        }

        if ($avgUtilization < 50) {
            $recommendations[] = [
                'type' => 'underutilization',
                'priority' => 'medium',
                'message' => 'Zone is underutilized. Consider consolidating inventory or repurposing space.'
            ];
        }

        return $recommendations;
    }

    private function calculateVariance($snapshots)
    {
        if ($snapshots->count() < 2) return 0;
        
        $mean = $snapshots->avg('utilization_percentage');
        $variance = $snapshots->reduce(function($carry, $snapshot) use ($mean) {
            return $carry + pow($snapshot->utilization_percentage - $mean, 2);
        }, 0) / $snapshots->count();
        
        return sqrt($variance);
    }

    private function calculateEfficiencyScore($zone, $snapshots)
    {
        $avgUtilization = $snapshots->avg('utilization_percentage');
        $variance = $this->calculateVariance($snapshots);
        
        // Efficiency score based on utilization and consistency
        $utilizationScore = min($avgUtilization / 80 * 100, 100); // Optimal around 80%
        $consistencyScore = max(100 - $variance, 0);
        
        return ($utilizationScore + $consistencyScore) / 2;
    }

    private function getCapacityStatus($zone, $snapshot)
    {
        if (!$snapshot) return 'unknown';
        
        $utilization = $snapshot->utilization_percentage;
        
        if ($utilization >= 95) return 'critical';
        if ($utilization >= 85) return 'high';
        if ($utilization >= 70) return 'optimal';
        if ($utilization >= 50) return 'moderate';
        return 'low';
    }

    private function getCurrentAlerts($zones)
    {
        $alerts = [];
        
        foreach ($zones as $zone) {
            $latestSnapshot = $zone->utilizationSnapshots->first();
            if ($latestSnapshot && $latestSnapshot->utilization_percentage > 90) {
                $alerts[] = [
                    'zone_id' => $zone->id,
                    'zone_name' => $zone->name,
                    'alert_type' => 'high_utilization',
                    'utilization' => $latestSnapshot->utilization_percentage,
                    'severity' => $latestSnapshot->utilization_percentage > 95 ? 'critical' : 'warning',
                    'timestamp' => $latestSnapshot->snapshot_time
                ];
            }
        }
        
        return $alerts;
    }

    private function getRecentTrends()
    {
        $snapshots = SpaceUtilizationSnapshot::where('snapshot_time', '>=', now()->subHours(24))
            ->orderBy('snapshot_time')
            ->get();

        return $snapshots->groupBy(function($snapshot) {
            return $snapshot->snapshot_time->format('H');
        })->map(function($hourSnapshots, $hour) {
            return [
                'hour' => (int)$hour,
                'average_utilization' => $hourSnapshots->avg('utilization_percentage')
            ];
        })->sortBy('hour')->values();
    }

    private function generateReportSummary($snapshots)
    {
        return [
            'total_snapshots' => $snapshots->count(),
            'zones_analyzed' => $snapshots->pluck('zone_id')->unique()->count(),
            'average_utilization' => $snapshots->avg('utilization_percentage'),
            'peak_utilization' => $snapshots->max('utilization_percentage'),
            'lowest_utilization' => $snapshots->min('utilization_percentage'),
            'total_occupied_locations' => $snapshots->sum('occupied_locations'),
            'total_available_locations' => $snapshots->sum('total_locations') - $snapshots->sum('occupied_locations')
        ];
    }

    private function generateZonePerformance($snapshots)
    {
        return $snapshots->groupBy('zone_id')->map(function($zoneSnapshots) {
            $zone = $zoneSnapshots->first()->zone;
            return [
                'zone_id' => $zone->id,
                'zone_name' => $zone->name,
                'zone_type' => $zone->type,
                'average_utilization' => $zoneSnapshots->avg('utilization_percentage'),
                'peak_utilization' => $zoneSnapshots->max('utilization_percentage'),
                'utilization_variance' => $this->calculateVariance($zoneSnapshots),
                'efficiency_score' => $this->calculateEfficiencyScore($zone, $zoneSnapshots)
            ];
        })->values();
    }

    private function generateTrendAnalysis($snapshots)
    {
        return $snapshots->groupBy(function($snapshot) {
            return $snapshot->snapshot_time->format('Y-m-d');
        })->map(function($daySnapshots, $date) {
            return [
                'date' => $date,
                'average_utilization' => $daySnapshots->avg('utilization_percentage'),
                'peak_utilization' => $daySnapshots->max('utilization_percentage'),
                'total_occupied' => $daySnapshots->sum('occupied_locations')
            ];
        })->sortBy('date')->values();
    }

    private function generateReportRecommendations($snapshots)
    {
        $recommendations = [];
        $zonePerformance = $this->generateZonePerformance($snapshots);
        
        foreach ($zonePerformance as $zone) {
            if ($zone['average_utilization'] > 90) {
                $recommendations[] = [
                    'zone_id' => $zone['zone_id'],
                    'zone_name' => $zone['zone_name'],
                    'type' => 'capacity_expansion',
                    'priority' => 'high',
                    'description' => 'High utilization detected. Consider capacity expansion or layout optimization.'
                ];
            }
            
            if ($zone['utilization_variance'] > 20) {
                $recommendations[] = [
                    'zone_id' => $zone['zone_id'],
                    'zone_name' => $zone['zone_name'],
                    'type' => 'utilization_stability',
                    'priority' => 'medium',
                    'description' => 'High utilization variance detected. Review inventory management processes.'
                ];
            }
        }
        
        return $recommendations;
    }
}