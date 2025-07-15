<?php

namespace App\Http\Controllers\Api\SpaceUtilization;

use App\Http\Controllers\Controller;
use App\Models\SpaceUtilization\WarehouseAisle;
use App\Models\SpaceUtilization\WarehouseZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class WarehouseAisleController extends Controller
{
    /**
     * Display a listing of warehouse aisles
     */
    public function index(Request $request): JsonResponse
    {
        $query = WarehouseAisle::with(['zone']);

        // Filter by zone
        if ($request->has('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
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

        $aisles = $request->has('per_page') 
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $aisles,
            'message' => 'Warehouse aisles retrieved successfully'
        ]);
    }

    /**
     * Store a newly created aisle
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => 'required|exists:warehouse_zones,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouse_aisles,code',
            'length' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'location_count' => 'required|integer|min:0',
            'occupied_locations' => 'nullable|integer|min:0',
            'coordinates' => 'nullable|array',
            'coordinates.x' => 'required_with:coordinates|numeric',
            'coordinates.y' => 'required_with:coordinates|numeric',
            'status' => 'nullable|in:active,inactive,maintenance'
        ]);

        $validated['status'] = $validated['status'] ?? 'active';
        
        $aisle = WarehouseAisle::create($validated);
        $aisle->updateUtilization();
        $aisle->load('zone');

        return response()->json([
            'success' => true,
            'data' => $aisle,
            'message' => 'Warehouse aisle created successfully'
        ], 201);
    }

    /**
     * Display the specified aisle
     */
    public function show(WarehouseAisle $aisle): JsonResponse
    {
        $aisle->load(['zone', 'efficiencyMetrics' => function($query) {
            $query->latest()->limit(30);
        }]);

        return response()->json([
            'success' => true,
            'data' => $aisle,
            'message' => 'Warehouse aisle retrieved successfully'
        ]);
    }

    /**
     * Update the specified aisle
     */
    public function update(Request $request, WarehouseAisle $aisle): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => 'sometimes|exists:warehouse_zones,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:warehouse_aisles,code,' . $aisle->id,
            'length' => 'sometimes|numeric|min:0',
            'width' => 'sometimes|numeric|min:0',
            'height' => 'sometimes|numeric|min:0',
            'location_count' => 'sometimes|integer|min:0',
            'occupied_locations' => 'sometimes|integer|min:0',
            'coordinates' => 'sometimes|array',
            'coordinates.x' => 'required_with:coordinates|numeric',
            'coordinates.y' => 'required_with:coordinates|numeric',
            'status' => 'sometimes|in:active,inactive,maintenance'
        ]);

        $aisle->update($validated);
        $aisle->updateUtilization();
        $aisle->load('zone');

        return response()->json([
            'success' => true,
            'data' => $aisle,
            'message' => 'Warehouse aisle updated successfully'
        ]);
    }

    /**
     * Remove the specified aisle
     */
    public function destroy(WarehouseAisle $aisle): JsonResponse
    {
        $aisle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse aisle deleted successfully'
        ]);
    }

    /**
     * Get aisle utilization analytics
     */
    public function utilization(WarehouseAisle $aisle): JsonResponse
    {
        $utilization = [
            'current_utilization' => $aisle->utilization_percentage,
            'available_locations' => $aisle->location_count - $aisle->occupied_locations,
            'capacity_status' => $aisle->getCapacityStatus(),
            'efficiency_trend' => $aisle->getEfficiencyTrend(),
            'recent_metrics' => $aisle->efficiencyMetrics()
                ->latest()
                ->limit(7)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $utilization,
            'message' => 'Aisle utilization data retrieved successfully'
        ]);
    }

    /**
     * Get aisle efficiency metrics
     */
    public function efficiency(WarehouseAisle $aisle, Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        
        $metrics = $aisle->efficiencyMetrics()
            ->where('metric_date', '>=', now()->subDays($days))
            ->orderBy('metric_date')
            ->get();

        $analytics = [
            'average_efficiency' => $metrics->avg('efficiency_score'),
            'average_pick_density' => $metrics->avg('pick_density'),
            'average_travel_distance' => $metrics->avg('travel_distance'),
            'total_congestion_incidents' => $metrics->sum('congestion_incidents'),
            'trend_data' => $metrics->map(function($metric) {
                return [
                    'date' => $metric->metric_date,
                    'efficiency_score' => $metric->efficiency_score,
                    'pick_density' => $metric->pick_density,
                    'accessibility_score' => $metric->accessibility_score
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Aisle efficiency metrics retrieved successfully'
        ]);
    }

    /**
     * Update aisle locations
     */
    public function updateLocations(Request $request, WarehouseAisle $aisle): JsonResponse
    {
        $validated = $request->validate([
            'occupied_locations' => 'required|integer|min:0|max:' . $aisle->location_count
        ]);

        $aisle->update($validated);
        $aisle->updateUtilization();

        return response()->json([
            'success' => true,
            'data' => $aisle,
            'message' => 'Aisle locations updated successfully'
        ]);
    }

    /**
     * Get aisles by zone
     */
    public function byZone(WarehouseZone $zone): JsonResponse
    {
        $aisles = $zone->aisles()
            ->with(['efficiencyMetrics' => function($query) {
                $query->latest()->limit(1);
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $aisles,
            'message' => 'Zone aisles retrieved successfully'
        ]);
    }

    /**
     * Get aisle performance comparison
     */
    public function performanceComparison(Request $request): JsonResponse
    {
        $zoneId = $request->get('zone_id');
        $metric = $request->get('metric', 'efficiency_score');
        $days = $request->get('days', 7);

        $query = WarehouseAisle::with(['efficiencyMetrics' => function($q) use ($days, $metric) {
            $q->where('metric_date', '>=', now()->subDays($days))
              ->select('aisle_id', 'metric_date', $metric);
        }]);

        if ($zoneId) {
            $query->where('zone_id', $zoneId);
        }

        $aisles = $query->get();

        $comparison = $aisles->map(function($aisle) use ($metric) {
            return [
                'aisle_id' => $aisle->id,
                'aisle_name' => $aisle->name,
                'aisle_code' => $aisle->code,
                'zone_name' => $aisle->zone->name,
                'average_metric' => $aisle->efficiencyMetrics->avg($metric),
                'current_utilization' => $aisle->utilization_percentage,
                'status' => $aisle->status
            ];
        })->sortByDesc('average_metric');

        return response()->json([
            'success' => true,
            'data' => $comparison->values(),
            'message' => 'Aisle performance comparison retrieved successfully'
        ]);
    }
}