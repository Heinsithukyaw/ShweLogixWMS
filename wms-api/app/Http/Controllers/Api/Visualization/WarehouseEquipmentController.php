<?php

namespace App\Http\Controllers\Api\Visualization;

use App\Http\Controllers\Controller;
use App\Models\Visualization\WarehouseEquipment;
use App\Models\Visualization\EquipmentMovement;
use App\Models\SpaceUtilization\WarehouseZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class WarehouseEquipmentController extends Controller
{
    /**
     * Display a listing of warehouse equipment
     */
    public function index(Request $request): JsonResponse
    {
        $query = WarehouseEquipment::with(['currentZone', 'recentMovements' => function($q) {
            $q->latest()->limit(5);
        }]);

        // Filter by equipment type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by zone
        if ($request->has('zone_id')) {
            $query->where('current_zone_id', $request->zone_id);
        }

        // Filter by battery level (for applicable equipment)
        if ($request->has('low_battery')) {
            $query->where('battery_level', '<', 20)->whereNotNull('battery_level');
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $equipment = $request->has('per_page') 
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $equipment,
            'message' => 'Warehouse equipment retrieved successfully'
        ]);
    }

    /**
     * Store a newly created equipment
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouse_equipment,code',
            'type' => 'required|in:forklift,conveyor,scanner,robot,crane,agv,sorter',
            'status' => 'nullable|in:active,inactive,maintenance,offline',
            'current_x' => 'required|numeric',
            'current_y' => 'required|numeric',
            'current_z' => 'nullable|numeric',
            'current_zone_id' => 'nullable|exists:warehouse_zones,id',
            'specifications' => 'nullable|array',
            'battery_level' => 'nullable|integer|between:0,100',
            'sensor_data' => 'nullable|array'
        ]);

        $validated['status'] = $validated['status'] ?? 'active';
        $validated['current_z'] = $validated['current_z'] ?? 0;
        $validated['last_activity'] = now();

        $equipment = WarehouseEquipment::create($validated);
        $equipment->load(['currentZone']);

        // Log initial position
        $this->logMovement($equipment, null, [
            'x' => $validated['current_x'],
            'y' => $validated['current_y'],
            'z' => $validated['current_z']
        ], 'initial_position');

        return response()->json([
            'success' => true,
            'data' => $equipment,
            'message' => 'Warehouse equipment created successfully'
        ], 201);
    }

    /**
     * Display the specified equipment
     */
    public function show(WarehouseEquipment $equipment): JsonResponse
    {
        $equipment->load([
            'currentZone',
            'movements' => function($query) {
                $query->latest()->limit(20);
            }
        ]);

        // Add calculated fields
        $equipment->total_distance = $equipment->movements->sum('distance_traveled');
        $equipment->average_speed = $equipment->movements->avg('speed');
        $equipment->uptime_percentage = $this->calculateUptime($equipment);

        return response()->json([
            'success' => true,
            'data' => $equipment,
            'message' => 'Warehouse equipment retrieved successfully'
        ]);
    }

    /**
     * Update the specified equipment
     */
    public function update(Request $request, WarehouseEquipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:warehouse_equipment,code,' . $equipment->id,
            'type' => 'sometimes|in:forklift,conveyor,scanner,robot,crane,agv,sorter',
            'status' => 'sometimes|in:active,inactive,maintenance,offline',
            'specifications' => 'sometimes|array',
            'battery_level' => 'sometimes|integer|between:0,100',
            'sensor_data' => 'sometimes|array'
        ]);

        $equipment->update($validated);
        $equipment->load(['currentZone']);

        return response()->json([
            'success' => true,
            'data' => $equipment,
            'message' => 'Warehouse equipment updated successfully'
        ]);
    }

    /**
     * Remove the specified equipment
     */
    public function destroy(WarehouseEquipment $equipment): JsonResponse
    {
        $equipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse equipment deleted successfully'
        ]);
    }

    /**
     * Update equipment position
     */
    public function updatePosition(Request $request, WarehouseEquipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'z' => 'nullable|numeric',
            'zone_id' => 'nullable|exists:warehouse_zones,id',
            'speed' => 'nullable|numeric|min:0',
            'direction' => 'nullable|numeric|between:0,360'
        ]);

        $oldPosition = [
            'x' => $equipment->current_x,
            'y' => $equipment->current_y,
            'z' => $equipment->current_z
        ];

        $newPosition = [
            'x' => $validated['x'],
            'y' => $validated['y'],
            'z' => $validated['z'] ?? $equipment->current_z
        ];

        // Update equipment position
        $equipment->update([
            'current_x' => $newPosition['x'],
            'current_y' => $newPosition['y'],
            'current_z' => $newPosition['z'],
            'current_zone_id' => $validated['zone_id'] ?? $equipment->current_zone_id,
            'last_activity' => now()
        ]);

        // Log movement
        $this->logMovement($equipment, $oldPosition, $newPosition, 'position_update', [
            'speed' => $validated['speed'] ?? null,
            'direction' => $validated['direction'] ?? null
        ]);

        $equipment->load(['currentZone']);

        return response()->json([
            'success' => true,
            'data' => $equipment,
            'message' => 'Equipment position updated successfully'
        ]);
    }

    /**
     * Get equipment movement history
     */
    public function movements(WarehouseEquipment $equipment, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'movement_type' => 'nullable|in:position_update,zone_change,task_movement,maintenance_move',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = $equipment->movements()->with(['fromZone', 'toZone']);

        if (isset($validated['start_time'])) {
            $query->where('movement_time', '>=', $validated['start_time']);
        }

        if (isset($validated['end_time'])) {
            $query->where('movement_time', '<=', $validated['end_time']);
        }

        if (isset($validated['movement_type'])) {
            $query->where('movement_type', $validated['movement_type']);
        }

        $query->orderBy('movement_time', 'desc');

        $movements = $request->has('per_page') 
            ? $query->paginate($validated['per_page'] ?? 15)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $movements,
            'message' => 'Equipment movements retrieved successfully'
        ]);
    }

    /**
     * Get equipment analytics
     */
    public function analytics(WarehouseEquipment $equipment, Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        $startDate = now()->subDays($days);

        $movements = $equipment->movements()
            ->where('movement_time', '>=', $startDate)
            ->get();

        $analytics = [
            'equipment_info' => [
                'id' => $equipment->id,
                'name' => $equipment->name,
                'code' => $equipment->code,
                'type' => $equipment->type,
                'status' => $equipment->status
            ],
            'movement_statistics' => [
                'total_movements' => $movements->count(),
                'total_distance' => $movements->sum('distance_traveled'),
                'average_speed' => $movements->avg('speed'),
                'max_speed' => $movements->max('speed'),
                'active_time' => $this->calculateActiveTime($movements),
                'zones_visited' => $movements->pluck('to_zone_id')->unique()->count()
            ],
            'utilization_metrics' => [
                'uptime_percentage' => $this->calculateUptime($equipment, $days),
                'movement_efficiency' => $this->calculateMovementEfficiency($movements),
                'zone_distribution' => $this->getZoneDistribution($movements),
                'hourly_activity' => $this->getHourlyActivity($movements)
            ],
            'performance_trends' => [
                'daily_distance' => $this->getDailyDistance($movements),
                'speed_trends' => $this->getSpeedTrends($movements),
                'utilization_trend' => $this->getUtilizationTrend($equipment, $days)
            ],
            'maintenance_insights' => [
                'battery_health' => $this->getBatteryHealth($equipment),
                'sensor_alerts' => $this->getSensorAlerts($equipment),
                'maintenance_recommendations' => $this->getMaintenanceRecommendations($equipment, $movements)
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Equipment analytics retrieved successfully'
        ]);
    }

    /**
     * Get real-time equipment status
     */
    public function realTimeStatus(Request $request): JsonResponse
    {
        $query = WarehouseEquipment::with(['currentZone']);

        // Filter by equipment types
        if ($request->has('types')) {
            $query->whereIn('type', $request->types);
        }

        // Filter by zones
        if ($request->has('zone_ids')) {
            $query->whereIn('current_zone_id', $request->zone_ids);
        }

        // Only active equipment
        $query->where('status', 'active');

        $equipment = $query->get();

        $realTimeData = $equipment->map(function($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'type' => $item->type,
                'status' => $item->status,
                'position' => [
                    'x' => $item->current_x,
                    'y' => $item->current_y,
                    'z' => $item->current_z
                ],
                'zone' => $item->currentZone ? [
                    'id' => $item->currentZone->id,
                    'name' => $item->currentZone->name,
                    'code' => $item->currentZone->code
                ] : null,
                'battery_level' => $item->battery_level,
                'last_activity' => $item->last_activity,
                'sensor_data' => $item->sensor_data,
                'alerts' => $this->getEquipmentAlerts($item)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'equipment' => $realTimeData,
                'summary' => [
                    'total_equipment' => $equipment->count(),
                    'active_equipment' => $equipment->where('status', 'active')->count(),
                    'low_battery_count' => $equipment->where('battery_level', '<', 20)->count(),
                    'last_updated' => now()
                ]
            ],
            'message' => 'Real-time equipment status retrieved successfully'
        ]);
    }

    /**
     * Get equipment by zone
     */
    public function byZone(WarehouseZone $zone): JsonResponse
    {
        $equipment = $zone->equipment()->with(['recentMovements' => function($q) {
            $q->latest()->limit(3);
        }])->get();

        return response()->json([
            'success' => true,
            'data' => [
                'zone_info' => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'code' => $zone->code,
                    'type' => $zone->type
                ],
                'equipment' => $equipment,
                'summary' => [
                    'total_equipment' => $equipment->count(),
                    'equipment_by_type' => $equipment->groupBy('type')->map->count(),
                    'active_equipment' => $equipment->where('status', 'active')->count()
                ]
            ],
            'message' => 'Zone equipment retrieved successfully'
        ]);
    }

    /**
     * Track equipment path
     */
    public function trackPath(WarehouseEquipment $equipment, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'simplify' => 'nullable|boolean'
        ]);

        $startTime = $validated['start_time'] ?? now()->subHours(24);
        $endTime = $validated['end_time'] ?? now();

        $movements = $equipment->movements()
            ->whereBetween('movement_time', [$startTime, $endTime])
            ->orderBy('movement_time')
            ->get();

        $path = $movements->map(function($movement) {
            return [
                'timestamp' => $movement->movement_time,
                'from_position' => [
                    'x' => $movement->from_x,
                    'y' => $movement->from_y,
                    'z' => $movement->from_z
                ],
                'to_position' => [
                    'x' => $movement->to_x,
                    'y' => $movement->to_y,
                    'z' => $movement->to_z
                ],
                'distance' => $movement->distance_traveled,
                'speed' => $movement->speed,
                'movement_type' => $movement->movement_type,
                'zone_change' => $movement->from_zone_id !== $movement->to_zone_id
            ];
        });

        // Simplify path if requested
        if ($validated['simplify'] ?? false) {
            $path = $this->simplifyPath($path);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'equipment_info' => [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'code' => $equipment->code
                ],
                'time_period' => [
                    'start_time' => $startTime,
                    'end_time' => $endTime
                ],
                'path' => $path,
                'statistics' => [
                    'total_movements' => $path->count(),
                    'total_distance' => $movements->sum('distance_traveled'),
                    'average_speed' => $movements->avg('speed'),
                    'duration' => $endTime->diffInMinutes($startTime) . ' minutes'
                ]
            ],
            'message' => 'Equipment path tracked successfully'
        ]);
    }

    // Private helper methods

    private function logMovement($equipment, $oldPosition, $newPosition, $movementType, $additionalData = [])
    {
        $distance = $oldPosition ? $this->calculateDistance($oldPosition, $newPosition) : 0;

        EquipmentMovement::create([
            'equipment_id' => $equipment->id,
            'movement_time' => now(),
            'from_x' => $oldPosition['x'] ?? null,
            'from_y' => $oldPosition['y'] ?? null,
            'from_z' => $oldPosition['z'] ?? null,
            'to_x' => $newPosition['x'],
            'to_y' => $newPosition['y'],
            'to_z' => $newPosition['z'],
            'from_zone_id' => $equipment->getOriginal('current_zone_id'),
            'to_zone_id' => $equipment->current_zone_id,
            'distance_traveled' => $distance,
            'speed' => $additionalData['speed'] ?? null,
            'direction' => $additionalData['direction'] ?? null,
            'movement_type' => $movementType,
            'metadata' => $additionalData
        ]);
    }

    private function calculateDistance($from, $to)
    {
        return sqrt(
            pow($to['x'] - $from['x'], 2) + 
            pow($to['y'] - $from['y'], 2) + 
            pow($to['z'] - $from['z'], 2)
        );
    }

    private function calculateUptime($equipment, $days = 7)
    {
        $totalMinutes = $days * 24 * 60;
        $activeMinutes = $equipment->movements()
            ->where('movement_time', '>=', now()->subDays($days))
            ->count() * 5; // Assuming 5 minutes per movement on average

        return min(($activeMinutes / $totalMinutes) * 100, 100);
    }

    private function calculateActiveTime($movements)
    {
        if ($movements->isEmpty()) return 0;
        
        $firstMovement = $movements->min('movement_time');
        $lastMovement = $movements->max('movement_time');
        
        return Carbon::parse($firstMovement)->diffInMinutes(Carbon::parse($lastMovement));
    }

    private function calculateMovementEfficiency($movements)
    {
        if ($movements->isEmpty()) return 0;
        
        $totalDistance = $movements->sum('distance_traveled');
        $totalTime = $this->calculateActiveTime($movements);
        
        return $totalTime > 0 ? $totalDistance / $totalTime : 0;
    }

    private function getZoneDistribution($movements)
    {
        return $movements->groupBy('to_zone_id')->map(function($zoneMovements, $zoneId) {
            $zone = WarehouseZone::find($zoneId);
            return [
                'zone_id' => $zoneId,
                'zone_name' => $zone?->name ?? 'Unknown',
                'visit_count' => $zoneMovements->count(),
                'total_distance' => $zoneMovements->sum('distance_traveled')
            ];
        })->values();
    }

    private function getHourlyActivity($movements)
    {
        return $movements->groupBy(function($movement) {
            return Carbon::parse($movement->movement_time)->format('H');
        })->map(function($hourMovements, $hour) {
            return [
                'hour' => (int)$hour,
                'movement_count' => $hourMovements->count(),
                'total_distance' => $hourMovements->sum('distance_traveled'),
                'average_speed' => $hourMovements->avg('speed')
            ];
        })->sortBy('hour')->values();
    }

    private function getDailyDistance($movements)
    {
        return $movements->groupBy(function($movement) {
            return Carbon::parse($movement->movement_time)->format('Y-m-d');
        })->map(function($dayMovements, $date) {
            return [
                'date' => $date,
                'total_distance' => $dayMovements->sum('distance_traveled'),
                'movement_count' => $dayMovements->count()
            ];
        })->sortBy('date')->values();
    }

    private function getSpeedTrends($movements)
    {
        return $movements->groupBy(function($movement) {
            return Carbon::parse($movement->movement_time)->format('Y-m-d H');
        })->map(function($hourMovements, $hour) {
            return [
                'hour' => $hour,
                'average_speed' => $hourMovements->avg('speed'),
                'max_speed' => $hourMovements->max('speed')
            ];
        })->sortBy('hour')->values();
    }

    private function getUtilizationTrend($equipment, $days)
    {
        $dailyMovements = $equipment->movements()
            ->where('movement_time', '>=', now()->subDays($days))
            ->get()
            ->groupBy(function($movement) {
                return Carbon::parse($movement->movement_time)->format('Y-m-d');
            });

        return $dailyMovements->map(function($dayMovements, $date) {
            return [
                'date' => $date,
                'utilization_score' => min($dayMovements->count() * 2, 100) // Simple utilization score
            ];
        })->sortBy('date')->values();
    }

    private function getBatteryHealth($equipment)
    {
        if (!$equipment->battery_level) return null;
        
        return [
            'current_level' => $equipment->battery_level,
            'status' => $equipment->battery_level < 20 ? 'low' : ($equipment->battery_level < 50 ? 'medium' : 'good'),
            'estimated_runtime' => $this->estimateRuntimeMinutes($equipment->battery_level),
            'charging_needed' => $equipment->battery_level < 30
        ];
    }

    private function getSensorAlerts($equipment)
    {
        $alerts = [];
        $sensorData = $equipment->sensor_data ?? [];
        
        if (isset($sensorData['temperature']) && $sensorData['temperature'] > 80) {
            $alerts[] = [
                'type' => 'temperature',
                'severity' => 'warning',
                'message' => 'High temperature detected',
                'value' => $sensorData['temperature']
            ];
        }
        
        if (isset($sensorData['vibration']) && $sensorData['vibration'] > 8) {
            $alerts[] = [
                'type' => 'vibration',
                'severity' => 'warning',
                'message' => 'High vibration levels detected',
                'value' => $sensorData['vibration']
            ];
        }
        
        return $alerts;
    }

    private function getMaintenanceRecommendations($equipment, $movements)
    {
        $recommendations = [];
        
        $totalDistance = $movements->sum('distance_traveled');
        if ($totalDistance > 1000) {
            $recommendations[] = [
                'type' => 'routine_maintenance',
                'priority' => 'medium',
                'message' => 'High usage detected. Schedule routine maintenance.',
                'due_date' => now()->addDays(7)
            ];
        }
        
        if ($equipment->battery_level && $equipment->battery_level < 20) {
            $recommendations[] = [
                'type' => 'battery_replacement',
                'priority' => 'high',
                'message' => 'Battery level critically low. Immediate charging required.',
                'due_date' => now()
            ];
        }
        
        return $recommendations;
    }

    private function getEquipmentAlerts($equipment)
    {
        $alerts = [];
        
        if ($equipment->battery_level && $equipment->battery_level < 20) {
            $alerts[] = [
                'type' => 'low_battery',
                'severity' => 'warning',
                'message' => 'Battery level is low'
            ];
        }
        
        if ($equipment->last_activity && $equipment->last_activity < now()->subMinutes(30)) {
            $alerts[] = [
                'type' => 'inactive',
                'severity' => 'info',
                'message' => 'Equipment has been inactive for over 30 minutes'
            ];
        }
        
        return $alerts;
    }

    private function estimateRuntimeMinutes($batteryLevel)
    {
        // Simple estimation: assume 100% battery = 8 hours runtime
        return ($batteryLevel / 100) * 480;
    }

    private function simplifyPath($path)
    {
        // Simple path simplification - remove points that are too close together
        $simplified = collect();
        $lastPoint = null;
        
        foreach ($path as $point) {
            if (!$lastPoint || $this->calculateDistance(
                $lastPoint['to_position'], 
                $point['to_position']
            ) > 5) { // 5 unit threshold
                $simplified->push($point);
                $lastPoint = $point;
            }
        }
        
        return $simplified;
    }
}