<?php

namespace App\Http\Controllers\Api\SpaceUtilization;

use App\Http\Controllers\Controller;
use App\Models\SpaceUtilization\HeatMapData;
use App\Models\SpaceUtilization\WarehouseZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class HeatMapController extends Controller
{
    /**
     * Get heat map data for visualization
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'map_type' => 'required|in:utilization,activity,efficiency,temperature',
            'zone_id' => 'nullable|exists:warehouse_zones,id',
            'time_range' => 'nullable|in:1h,6h,24h,7d',
            'intensity_level' => 'nullable|in:low,medium,high,critical',
            'resolution' => 'nullable|in:low,medium,high'
        ]);

        $timeRange = $validated['time_range'] ?? '24h';
        $startTime = $this->getStartTime($timeRange);

        $query = HeatMapData::where('map_type', $validated['map_type'])
            ->where('data_time', '>=', $startTime);

        if (isset($validated['zone_id'])) {
            $query->where('zone_id', $validated['zone_id']);
        }

        if (isset($validated['intensity_level'])) {
            $query->where('intensity_level', $validated['intensity_level']);
        }

        $heatMapData = $query->with('zone')->get();

        // Process data for visualization
        $processedData = $this->processHeatMapData($heatMapData, $validated);

        return response()->json([
            'success' => true,
            'data' => [
                'heat_map_points' => $processedData,
                'metadata' => [
                    'map_type' => $validated['map_type'],
                    'time_range' => $timeRange,
                    'total_points' => $heatMapData->count(),
                    'zones_covered' => $heatMapData->pluck('zone_id')->unique()->count(),
                    'intensity_distribution' => $this->getIntensityDistribution($heatMapData),
                    'generated_at' => now()
                ]
            ],
            'message' => 'Heat map data retrieved successfully'
        ]);
    }

    /**
     * Create heat map data points
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data_points' => 'required|array|min:1',
            'data_points.*.map_type' => 'required|in:utilization,activity,efficiency,temperature',
            'data_points.*.zone_id' => 'required|exists:warehouse_zones,id',
            'data_points.*.x_coordinate' => 'required|numeric',
            'data_points.*.y_coordinate' => 'required|numeric',
            'data_points.*.intensity' => 'required|numeric|between:0,1',
            'data_points.*.metadata' => 'nullable|array'
        ]);

        $createdPoints = [];

        foreach ($validated['data_points'] as $pointData) {
            $pointData['intensity_level'] = $this->calculateIntensityLevel($pointData['intensity']);
            $pointData['data_time'] = now();

            $point = HeatMapData::create($pointData);
            $createdPoints[] = $point;
        }

        return response()->json([
            'success' => true,
            'data' => $createdPoints,
            'message' => 'Heat map data points created successfully'
        ], 201);
    }

    /**
     * Get heat map data for a specific zone
     */
    public function zoneHeatMap(WarehouseZone $zone, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'map_type' => 'required|in:utilization,activity,efficiency,temperature',
            'time_range' => 'nullable|in:1h,6h,24h,7d',
            'aggregation' => 'nullable|in:average,max,latest'
        ]);

        $timeRange = $validated['time_range'] ?? '24h';
        $aggregation = $validated['aggregation'] ?? 'latest';
        $startTime = $this->getStartTime($timeRange);

        $heatMapData = $zone->heatMapData()
            ->where('map_type', $validated['map_type'])
            ->where('data_time', '>=', $startTime)
            ->get();

        $processedData = $this->aggregateHeatMapData($heatMapData, $aggregation);

        $response = [
            'zone_info' => [
                'id' => $zone->id,
                'name' => $zone->name,
                'code' => $zone->code,
                'boundaries' => $zone->boundaries,
                'coordinates' => $zone->coordinates
            ],
            'heat_map_data' => $processedData,
            'statistics' => [
                'total_points' => $heatMapData->count(),
                'average_intensity' => $heatMapData->avg('intensity'),
                'max_intensity' => $heatMapData->max('intensity'),
                'intensity_distribution' => $this->getIntensityDistribution($heatMapData),
                'hot_spots' => $this->identifyHotSpots($heatMapData),
                'cold_spots' => $this->identifyColdSpots($heatMapData)
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $response,
            'message' => 'Zone heat map data retrieved successfully'
        ]);
    }

    /**
     * Get heat map analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'map_type' => 'required|in:utilization,activity,efficiency,temperature',
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'exists:warehouse_zones,id',
            'time_range' => 'nullable|in:1h,6h,24h,7d,30d'
        ]);

        $timeRange = $validated['time_range'] ?? '24h';
        $startTime = $this->getStartTime($timeRange);

        $query = HeatMapData::where('map_type', $validated['map_type'])
            ->where('data_time', '>=', $startTime);

        if (isset($validated['zone_ids'])) {
            $query->whereIn('zone_id', $validated['zone_ids']);
        }

        $heatMapData = $query->with('zone')->get();

        $analytics = [
            'overview' => [
                'total_data_points' => $heatMapData->count(),
                'zones_analyzed' => $heatMapData->pluck('zone_id')->unique()->count(),
                'time_period' => $timeRange,
                'map_type' => $validated['map_type']
            ],
            'intensity_analysis' => [
                'average_intensity' => $heatMapData->avg('intensity'),
                'max_intensity' => $heatMapData->max('intensity'),
                'min_intensity' => $heatMapData->min('intensity'),
                'intensity_distribution' => $this->getIntensityDistribution($heatMapData),
                'intensity_trends' => $this->getIntensityTrends($heatMapData)
            ],
            'spatial_analysis' => [
                'hot_spots' => $this->identifyHotSpots($heatMapData),
                'cold_spots' => $this->identifyColdSpots($heatMapData),
                'zone_comparison' => $this->compareZoneIntensities($heatMapData)
            ],
            'temporal_analysis' => [
                'hourly_patterns' => $this->getHourlyPatterns($heatMapData),
                'peak_times' => $this->identifyPeakTimes($heatMapData),
                'trend_analysis' => $this->analyzeTrends($heatMapData)
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Heat map analytics retrieved successfully'
        ]);
    }

    /**
     * Get real-time heat map overlay
     */
    public function realTimeOverlay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'map_type' => 'required|in:utilization,activity,efficiency,temperature',
            'zone_id' => 'nullable|exists:warehouse_zones,id',
            'refresh_interval' => 'nullable|integer|min:30|max:3600'
        ]);

        $refreshInterval = $validated['refresh_interval'] ?? 300; // 5 minutes default
        $cutoffTime = now()->subSeconds($refreshInterval);

        $query = HeatMapData::where('map_type', $validated['map_type'])
            ->where('data_time', '>=', $cutoffTime);

        if (isset($validated['zone_id'])) {
            $query->where('zone_id', $validated['zone_id']);
        }

        $realtimeData = $query->with('zone')->get();

        $overlay = [
            'data_points' => $realtimeData->map(function($point) {
                return [
                    'x' => $point->x_coordinate,
                    'y' => $point->y_coordinate,
                    'intensity' => $point->intensity,
                    'intensity_level' => $point->intensity_level,
                    'zone_id' => $point->zone_id,
                    'zone_name' => $point->zone->name,
                    'timestamp' => $point->data_time,
                    'metadata' => $point->metadata
                ];
            }),
            'overlay_config' => [
                'map_type' => $validated['map_type'],
                'refresh_interval' => $refreshInterval,
                'last_updated' => now(),
                'data_freshness' => $cutoffTime,
                'color_scale' => $this->getColorScale($validated['map_type'])
            ],
            'statistics' => [
                'active_points' => $realtimeData->count(),
                'average_intensity' => $realtimeData->avg('intensity'),
                'zones_active' => $realtimeData->pluck('zone_id')->unique()->count()
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $overlay,
            'message' => 'Real-time heat map overlay retrieved successfully'
        ]);
    }

    /**
     * Generate heat map report
     */
    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'map_type' => 'required|in:utilization,activity,efficiency,temperature',
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'exists:warehouse_zones,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'include_recommendations' => 'nullable|boolean'
        ]);

        $heatMapData = HeatMapData::where('map_type', $validated['map_type'])
            ->whereBetween('data_time', [$validated['start_date'], $validated['end_date']]);

        if (isset($validated['zone_ids'])) {
            $heatMapData->whereIn('zone_id', $validated['zone_ids']);
        }

        $data = $heatMapData->with('zone')->get();

        $report = [
            'report_info' => [
                'map_type' => $validated['map_type'],
                'period' => [
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date']
                ],
                'generated_at' => now()
            ],
            'summary' => [
                'total_data_points' => $data->count(),
                'zones_analyzed' => $data->pluck('zone_id')->unique()->count(),
                'average_intensity' => $data->avg('intensity'),
                'peak_intensity' => $data->max('intensity'),
                'intensity_distribution' => $this->getIntensityDistribution($data)
            ],
            'zone_analysis' => $this->generateZoneAnalysis($data),
            'temporal_patterns' => $this->generateTemporalPatterns($data),
            'insights' => $this->generateInsights($data, $validated['map_type'])
        ];

        if ($validated['include_recommendations'] ?? false) {
            $report['recommendations'] = $this->generateRecommendations($data, $validated['map_type']);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
            'message' => 'Heat map report generated successfully'
        ]);
    }

    // Private helper methods

    private function getStartTime(string $timeRange): Carbon
    {
        return match($timeRange) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay()
        };
    }

    private function calculateIntensityLevel(float $intensity): string
    {
        if ($intensity >= 0.8) return 'critical';
        if ($intensity >= 0.6) return 'high';
        if ($intensity >= 0.4) return 'medium';
        return 'low';
    }

    private function processHeatMapData($heatMapData, $params)
    {
        $resolution = $params['resolution'] ?? 'medium';
        $gridSize = match($resolution) {
            'low' => 10,
            'medium' => 5,
            'high' => 2,
            default => 5
        };

        return $heatMapData->map(function($point) use ($gridSize) {
            return [
                'x' => round($point->x_coordinate / $gridSize) * $gridSize,
                'y' => round($point->y_coordinate / $gridSize) * $gridSize,
                'intensity' => $point->intensity,
                'intensity_level' => $point->intensity_level,
                'zone_id' => $point->zone_id,
                'zone_name' => $point->zone->name,
                'timestamp' => $point->data_time,
                'metadata' => $point->metadata
            ];
        })->groupBy(function($point) {
            return $point['x'] . ',' . $point['y'];
        })->map(function($group) {
            $first = $group->first();
            return [
                'x' => $first['x'],
                'y' => $first['y'],
                'intensity' => $group->avg('intensity'),
                'intensity_level' => $this->calculateIntensityLevel($group->avg('intensity')),
                'zone_id' => $first['zone_id'],
                'zone_name' => $first['zone_name'],
                'point_count' => $group->count(),
                'latest_timestamp' => $group->max('timestamp')
            ];
        })->values();
    }

    private function aggregateHeatMapData($heatMapData, $aggregation)
    {
        return $heatMapData->groupBy(function($point) {
            return round($point->x_coordinate, 1) . ',' . round($point->y_coordinate, 1);
        })->map(function($group) use ($aggregation) {
            $intensity = match($aggregation) {
                'average' => $group->avg('intensity'),
                'max' => $group->max('intensity'),
                'latest' => $group->sortByDesc('data_time')->first()->intensity,
                default => $group->avg('intensity')
            };

            $first = $group->first();
            return [
                'x' => $first->x_coordinate,
                'y' => $first->y_coordinate,
                'intensity' => $intensity,
                'intensity_level' => $this->calculateIntensityLevel($intensity),
                'data_points' => $group->count(),
                'latest_update' => $group->max('data_time')
            ];
        })->values();
    }

    private function getIntensityDistribution($heatMapData)
    {
        return [
            'low' => $heatMapData->where('intensity_level', 'low')->count(),
            'medium' => $heatMapData->where('intensity_level', 'medium')->count(),
            'high' => $heatMapData->where('intensity_level', 'high')->count(),
            'critical' => $heatMapData->where('intensity_level', 'critical')->count()
        ];
    }

    private function identifyHotSpots($heatMapData)
    {
        return $heatMapData->where('intensity', '>=', 0.7)
            ->groupBy('zone_id')
            ->map(function($zoneData, $zoneId) {
                $zone = $zoneData->first()->zone;
                return [
                    'zone_id' => $zoneId,
                    'zone_name' => $zone->name,
                    'hot_spot_count' => $zoneData->count(),
                    'average_intensity' => $zoneData->avg('intensity'),
                    'peak_intensity' => $zoneData->max('intensity'),
                    'coordinates' => $zoneData->map(function($point) {
                        return ['x' => $point->x_coordinate, 'y' => $point->y_coordinate];
                    })
                ];
            })->values();
    }

    private function identifyColdSpots($heatMapData)
    {
        return $heatMapData->where('intensity', '<=', 0.3)
            ->groupBy('zone_id')
            ->map(function($zoneData, $zoneId) {
                $zone = $zoneData->first()->zone;
                return [
                    'zone_id' => $zoneId,
                    'zone_name' => $zone->name,
                    'cold_spot_count' => $zoneData->count(),
                    'average_intensity' => $zoneData->avg('intensity'),
                    'lowest_intensity' => $zoneData->min('intensity')
                ];
            })->values();
    }

    private function getIntensityTrends($heatMapData)
    {
        return $heatMapData->groupBy(function($point) {
            return $point->data_time->format('Y-m-d H');
        })->map(function($hourData, $hour) {
            return [
                'hour' => $hour,
                'average_intensity' => $hourData->avg('intensity'),
                'max_intensity' => $hourData->max('intensity'),
                'data_points' => $hourData->count()
            ];
        })->sortBy('hour')->values();
    }

    private function compareZoneIntensities($heatMapData)
    {
        return $heatMapData->groupBy('zone_id')->map(function($zoneData, $zoneId) {
            $zone = $zoneData->first()->zone;
            return [
                'zone_id' => $zoneId,
                'zone_name' => $zone->name,
                'zone_type' => $zone->type,
                'average_intensity' => $zoneData->avg('intensity'),
                'max_intensity' => $zoneData->max('intensity'),
                'data_points' => $zoneData->count(),
                'intensity_variance' => $this->calculateVariance($zoneData->pluck('intensity'))
            ];
        })->sortByDesc('average_intensity')->values();
    }

    private function getHourlyPatterns($heatMapData)
    {
        return $heatMapData->groupBy(function($point) {
            return $point->data_time->format('H');
        })->map(function($hourData, $hour) {
            return [
                'hour' => (int)$hour,
                'average_intensity' => $hourData->avg('intensity'),
                'peak_intensity' => $hourData->max('intensity'),
                'data_points' => $hourData->count()
            ];
        })->sortBy('hour')->values();
    }

    private function identifyPeakTimes($heatMapData)
    {
        $hourlyData = $this->getHourlyPatterns($heatMapData);
        return $hourlyData->sortByDesc('average_intensity')->take(3)->values();
    }

    private function analyzeTrends($heatMapData)
    {
        $dailyData = $heatMapData->groupBy(function($point) {
            return $point->data_time->format('Y-m-d');
        })->map(function($dayData, $date) {
            return [
                'date' => $date,
                'average_intensity' => $dayData->avg('intensity')
            ];
        })->sortBy('date');

        if ($dailyData->count() < 2) {
            return ['trend' => 'insufficient_data'];
        }

        $first = $dailyData->first()['average_intensity'];
        $last = $dailyData->last()['average_intensity'];
        $change = (($last - $first) / $first) * 100;

        return [
            'trend' => $change > 5 ? 'increasing' : ($change < -5 ? 'decreasing' : 'stable'),
            'change_percentage' => round($change, 2),
            'daily_data' => $dailyData->values()
        ];
    }

    private function getColorScale($mapType)
    {
        return match($mapType) {
            'utilization' => ['#00ff00', '#ffff00', '#ff8000', '#ff0000'],
            'activity' => ['#0000ff', '#00ffff', '#ffff00', '#ff0000'],
            'efficiency' => ['#ff0000', '#ff8000', '#ffff00', '#00ff00'],
            'temperature' => ['#0000ff', '#00ff00', '#ffff00', '#ff0000'],
            default => ['#00ff00', '#ffff00', '#ff8000', '#ff0000']
        };
    }

    private function calculateVariance($values)
    {
        if ($values->count() < 2) return 0;
        
        $mean = $values->avg();
        $variance = $values->reduce(function($carry, $value) use ($mean) {
            return $carry + pow($value - $mean, 2);
        }, 0) / $values->count();
        
        return sqrt($variance);
    }

    private function generateZoneAnalysis($data)
    {
        return $data->groupBy('zone_id')->map(function($zoneData, $zoneId) {
            $zone = $zoneData->first()->zone;
            return [
                'zone_id' => $zoneId,
                'zone_name' => $zone->name,
                'zone_type' => $zone->type,
                'total_data_points' => $zoneData->count(),
                'average_intensity' => $zoneData->avg('intensity'),
                'peak_intensity' => $zoneData->max('intensity'),
                'intensity_variance' => $this->calculateVariance($zoneData->pluck('intensity')),
                'hot_spots' => $zoneData->where('intensity', '>=', 0.7)->count(),
                'cold_spots' => $zoneData->where('intensity', '<=', 0.3)->count()
            ];
        })->values();
    }

    private function generateTemporalPatterns($data)
    {
        return [
            'hourly_patterns' => $this->getHourlyPatterns($data),
            'daily_patterns' => $data->groupBy(function($point) {
                return $point->data_time->format('Y-m-d');
            })->map(function($dayData, $date) {
                return [
                    'date' => $date,
                    'average_intensity' => $dayData->avg('intensity'),
                    'peak_intensity' => $dayData->max('intensity'),
                    'data_points' => $dayData->count()
                ];
            })->sortBy('date')->values(),
            'peak_times' => $this->identifyPeakTimes($data)
        ];
    }

    private function generateInsights($data, $mapType)
    {
        $insights = [];
        $avgIntensity = $data->avg('intensity');
        
        if ($avgIntensity > 0.7) {
            $insights[] = [
                'type' => 'high_intensity',
                'message' => "High average {$mapType} intensity detected across analyzed areas.",
                'severity' => 'warning'
            ];
        }
        
        $hotSpots = $this->identifyHotSpots($data);
        if ($hotSpots->count() > 0) {
            $insights[] = [
                'type' => 'hot_spots',
                'message' => "Identified {$hotSpots->count()} hot spots requiring attention.",
                'severity' => 'info'
            ];
        }
        
        return $insights;
    }

    private function generateRecommendations($data, $mapType)
    {
        $recommendations = [];
        $zoneAnalysis = $this->generateZoneAnalysis($data);
        
        foreach ($zoneAnalysis as $zone) {
            if ($zone['average_intensity'] > 0.8) {
                $recommendations[] = [
                    'zone_id' => $zone['zone_id'],
                    'zone_name' => $zone['zone_name'],
                    'type' => 'high_intensity_action',
                    'priority' => 'high',
                    'description' => "Zone shows consistently high {$mapType} intensity. Consider optimization measures."
                ];
            }
            
            if ($zone['intensity_variance'] > 0.3) {
                $recommendations[] = [
                    'zone_id' => $zone['zone_id'],
                    'zone_name' => $zone['zone_name'],
                    'type' => 'intensity_stability',
                    'priority' => 'medium',
                    'description' => "High intensity variance detected. Review operational consistency."
                ];
            }
        }
        
        return $recommendations;
    }
}