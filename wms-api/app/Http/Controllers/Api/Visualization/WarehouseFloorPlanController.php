<?php

namespace App\Http\Controllers\Api\Visualization;

use App\Http\Controllers\Controller;
use App\Models\Visualization\WarehouseFloorPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WarehouseFloorPlanController extends Controller
{
    /**
     * Display a listing of floor plans
     */
    public function index(Request $request): JsonResponse
    {
        $query = WarehouseFloorPlan::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $floorPlans = $request->has('per_page') 
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $floorPlans,
            'message' => 'Floor plans retrieved successfully'
        ]);
    }

    /**
     * Store a newly created floor plan
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'version' => 'required|string|max:50',
            'total_length' => 'required|numeric|min:0',
            'total_width' => 'required|numeric|min:0',
            'total_height' => 'required|numeric|min:0',
            'scale_unit' => 'required|in:meters,feet,inches',
            'layout_data' => 'required|array',
            'grid_settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string'
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        // Deactivate other floor plans if this one is set as active
        if ($validated['is_active']) {
            WarehouseFloorPlan::where('is_active', true)->update(['is_active' => false]);
        }

        $floorPlan = WarehouseFloorPlan::create($validated);

        return response()->json([
            'success' => true,
            'data' => $floorPlan,
            'message' => 'Floor plan created successfully'
        ], 201);
    }

    /**
     * Display the specified floor plan
     */
    public function show(WarehouseFloorPlan $floorPlan): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $floorPlan,
            'message' => 'Floor plan retrieved successfully'
        ]);
    }

    /**
     * Update the specified floor plan
     */
    public function update(Request $request, WarehouseFloorPlan $floorPlan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'version' => 'sometimes|string|max:50',
            'total_length' => 'sometimes|numeric|min:0',
            'total_width' => 'sometimes|numeric|min:0',
            'total_height' => 'sometimes|numeric|min:0',
            'scale_unit' => 'sometimes|in:meters,feet,inches',
            'layout_data' => 'sometimes|array',
            'grid_settings' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
            'description' => 'sometimes|string'
        ]);

        // Deactivate other floor plans if this one is set as active
        if (isset($validated['is_active']) && $validated['is_active']) {
            WarehouseFloorPlan::where('id', '!=', $floorPlan->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $floorPlan->update($validated);

        return response()->json([
            'success' => true,
            'data' => $floorPlan,
            'message' => 'Floor plan updated successfully'
        ]);
    }

    /**
     * Remove the specified floor plan
     */
    public function destroy(WarehouseFloorPlan $floorPlan): JsonResponse
    {
        $floorPlan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Floor plan deleted successfully'
        ]);
    }

    /**
     * Get the active floor plan
     */
    public function active(): JsonResponse
    {
        $activeFloorPlan = WarehouseFloorPlan::where('is_active', true)->first();

        if (!$activeFloorPlan) {
            return response()->json([
                'success' => false,
                'message' => 'No active floor plan found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $activeFloorPlan,
            'message' => 'Active floor plan retrieved successfully'
        ]);
    }

    /**
     * Set a floor plan as active
     */
    public function setActive(WarehouseFloorPlan $floorPlan): JsonResponse
    {
        // Deactivate all other floor plans
        WarehouseFloorPlan::where('id', '!=', $floorPlan->id)->update(['is_active' => false]);
        
        // Activate the selected floor plan
        $floorPlan->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'data' => $floorPlan,
            'message' => 'Floor plan set as active successfully'
        ]);
    }

    /**
     * Get floor plan with equipment overlay
     */
    public function withEquipment(WarehouseFloorPlan $floorPlan): JsonResponse
    {
        $equipment = \App\Models\Visualization\WarehouseEquipment::where('status', 'active')->get();

        $floorPlanData = $floorPlan->toArray();
        $floorPlanData['equipment_overlay'] = $equipment->map(function($item) {
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
                'zone_id' => $item->current_zone_id,
                'last_activity' => $item->last_activity,
                'battery_level' => $item->battery_level
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $floorPlanData,
            'message' => 'Floor plan with equipment overlay retrieved successfully'
        ]);
    }

    /**
     * Get floor plan with zone utilization overlay
     */
    public function withUtilization(WarehouseFloorPlan $floorPlan): JsonResponse
    {
        $zones = \App\Models\SpaceUtilization\WarehouseZone::with(['utilizationSnapshots' => function($query) {
            $query->latest()->limit(1);
        }])->get();

        $floorPlanData = $floorPlan->toArray();
        $floorPlanData['utilization_overlay'] = $zones->map(function($zone) {
            $latestSnapshot = $zone->utilizationSnapshots->first();
            return [
                'zone_id' => $zone->id,
                'zone_name' => $zone->name,
                'zone_code' => $zone->code,
                'zone_type' => $zone->type,
                'coordinates' => $zone->coordinates,
                'boundaries' => $zone->boundaries,
                'current_utilization' => $latestSnapshot?->utilization_percentage ?? 0,
                'capacity_status' => $this->getCapacityStatus($latestSnapshot?->utilization_percentage ?? 0),
                'last_updated' => $latestSnapshot?->snapshot_time
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $floorPlanData,
            'message' => 'Floor plan with utilization overlay retrieved successfully'
        ]);
    }

    /**
     * Get floor plan with heat map overlay
     */
    public function withHeatMap(WarehouseFloorPlan $floorPlan, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'map_type' => 'required|in:utilization,activity,efficiency,temperature',
            'time_range' => 'nullable|in:1h,6h,24h',
            'intensity_threshold' => 'nullable|numeric|between:0,1'
        ]);

        $timeRange = $validated['time_range'] ?? '24h';
        $startTime = match($timeRange) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subDay(),
            default => now()->subDay()
        };

        $heatMapData = \App\Models\SpaceUtilization\HeatMapData::where('map_type', $validated['map_type'])
            ->where('data_time', '>=', $startTime);

        if (isset($validated['intensity_threshold'])) {
            $heatMapData->where('intensity', '>=', $validated['intensity_threshold']);
        }

        $heatMapPoints = $heatMapData->get();

        $floorPlanData = $floorPlan->toArray();
        $floorPlanData['heat_map_overlay'] = [
            'map_type' => $validated['map_type'],
            'time_range' => $timeRange,
            'data_points' => $heatMapPoints->map(function($point) {
                return [
                    'x' => $point->x_coordinate,
                    'y' => $point->y_coordinate,
                    'intensity' => $point->intensity,
                    'intensity_level' => $point->intensity_level,
                    'zone_id' => $point->zone_id,
                    'timestamp' => $point->data_time,
                    'metadata' => $point->metadata
                ];
            }),
            'statistics' => [
                'total_points' => $heatMapPoints->count(),
                'average_intensity' => $heatMapPoints->avg('intensity'),
                'max_intensity' => $heatMapPoints->max('intensity')
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $floorPlanData,
            'message' => 'Floor plan with heat map overlay retrieved successfully'
        ]);
    }

    // Private helper methods

    private function getCapacityStatus(float $utilization): string
    {
        if ($utilization >= 95) return 'critical';
        if ($utilization >= 85) return 'high';
        if ($utilization >= 70) return 'optimal';
        if ($utilization >= 50) return 'moderate';
        return 'low';
    }
}