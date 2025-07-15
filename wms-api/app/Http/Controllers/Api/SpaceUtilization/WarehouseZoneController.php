<?php

namespace App\Http\Controllers\Api\SpaceUtilization;

use App\Http\Controllers\Controller;
use App\Models\SpaceUtilization\WarehouseZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class WarehouseZoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WarehouseZone::with(['aisles', 'utilizationSnapshots' => function($q) {
            $q->latest('snapshot_time')->limit(1);
        }]);

        // Apply filters
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $zones = $request->has('per_page') 
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        // Add calculated fields
        if ($zones instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $zones->getCollection()->transform(function ($zone) {
                return $this->addCalculatedFields($zone);
            });
        } else {
            $zones->transform(function ($zone) {
                return $this->addCalculatedFields($zone);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $zones,
            'message' => 'Warehouse zones retrieved successfully'
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouse_zones,code',
            'type' => 'required|string|in:storage,picking,receiving,shipping,staging',
            'length' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'usable_area' => 'nullable|numeric|min:0',
            'usable_volume' => 'nullable|numeric|min:0',
            'max_capacity' => 'required|integer|min:0',
            'coordinates' => 'nullable|array',
            'boundaries' => 'nullable|array',
            'status' => 'string|in:active,inactive,maintenance',
            'description' => 'nullable|string'
        ]);

        $zone = new WarehouseZone($validated);
        $zone->updateCalculatedFields();

        return response()->json([
            'success' => true,
            'data' => $zone->load(['aisles']),
            'message' => 'Warehouse zone created successfully'
        ], 201);
    }

    public function show(WarehouseZone $zone): JsonResponse
    {
        $zone->load([
            'aisles',
            'utilizationSnapshots' => function($q) {
                $q->latest('snapshot_time')->limit(10);
            },
            'capacityTracking' => function($q) {
                $q->latest('tracking_date')->limit(7);
            }
        ]);

        $zone = $this->addCalculatedFields($zone);
        $zone->utilization_trend = $zone->getUtilizationTrend(30);

        return response()->json([
            'success' => true,
            'data' => $zone,
            'message' => 'Warehouse zone retrieved successfully'
        ]);
    }

    public function update(Request $request, WarehouseZone $zone): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'code' => ['string', 'max:50', Rule::unique('warehouse_zones')->ignore($zone->id)],
            'type' => 'string|in:storage,picking,receiving,shipping,staging',
            'length' => 'numeric|min:0',
            'width' => 'numeric|min:0',
            'height' => 'numeric|min:0',
            'usable_area' => 'nullable|numeric|min:0',
            'usable_volume' => 'nullable|numeric|min:0',
            'max_capacity' => 'integer|min:0',
            'coordinates' => 'nullable|array',
            'boundaries' => 'nullable|array',
            'status' => 'string|in:active,inactive,maintenance',
            'description' => 'nullable|string'
        ]);

        $zone->update($validated);
        
        // Recalculate if dimensions changed
        if (isset($validated['length']) || isset($validated['width']) || isset($validated['height'])) {
            $zone->updateCalculatedFields();
        }

        return response()->json([
            'success' => true,
            'data' => $zone->load(['aisles']),
            'message' => 'Warehouse zone updated successfully'
        ]);
    }

    public function destroy(WarehouseZone $zone): JsonResponse
    {
        $zone->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse zone deleted successfully'
        ]);
    }

    public function utilization(WarehouseZone $zone, Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $interval = $request->get('interval', 'daily'); // daily, hourly

        $utilizationData = $zone->getUtilizationTrend($days);
        
        // Group by interval if needed
        if ($interval === 'daily') {
            $utilizationData = $utilizationData->groupBy(function($item) {
                return $item->snapshot_time->format('Y-m-d');
            })->map(function($group) {
                return [
                    'date' => $group->first()->snapshot_time->format('Y-m-d'),
                    'avg_utilization' => $group->avg('utilization_percentage'),
                    'max_utilization' => $group->max('utilization_percentage'),
                    'min_utilization' => $group->min('utilization_percentage'),
                    'snapshots_count' => $group->count()
                ];
            })->values();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'zone' => $zone->only(['id', 'name', 'code']),
                'utilization_data' => $utilizationData,
                'summary' => [
                    'current_utilization' => $zone->current_utilization,
                    'available_capacity' => $zone->available_capacity,
                    'period_days' => $days,
                    'interval' => $interval
                ]
            ],
            'message' => 'Zone utilization data retrieved successfully'
        ]);
    }

    public function capacity(WarehouseZone $zone, Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        
        $capacityData = $zone->capacityTracking()
            ->where('tracking_date', '>=', now()->subDays($days))
            ->orderBy('tracking_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'zone' => $zone->only(['id', 'name', 'code', 'max_capacity']),
                'capacity_data' => $capacityData,
                'summary' => [
                    'max_capacity' => $zone->max_capacity,
                    'current_available' => $zone->available_capacity,
                    'avg_utilization' => $capacityData->avg('capacity_utilization'),
                    'peak_utilization' => $capacityData->max('peak_utilization')
                ]
            ],
            'message' => 'Zone capacity data retrieved successfully'
        ]);
    }

    public function heatMap(WarehouseZone $zone, Request $request): JsonResponse
    {
        $mapType = $request->get('map_type', 'utilization');
        $timeRange = null;
        
        if ($request->has('start_time') && $request->has('end_time')) {
            $timeRange = [
                'start' => $request->start_time,
                'end' => $request->end_time
            ];
        }

        $heatMapData = \App\Models\SpaceUtilization\HeatMapData::generateHeatMapData(
            $mapType, 
            $zone->id, 
            $timeRange
        );

        return response()->json([
            'success' => true,
            'data' => [
                'zone' => $zone->only(['id', 'name', 'code', 'length', 'width']),
                'map_type' => $mapType,
                'heat_map_data' => $heatMapData,
                'time_range' => $timeRange ?? ['start' => now()->subDay(), 'end' => now()]
            ],
            'message' => 'Heat map data retrieved successfully'
        ]);
    }

    private function addCalculatedFields(WarehouseZone $zone): WarehouseZone
    {
        $zone->current_utilization = $zone->current_utilization;
        $zone->available_capacity = $zone->available_capacity;
        $zone->total_aisles = $zone->aisles->count();
        $zone->active_aisles = $zone->aisles->where('status', 'active')->count();
        
        return $zone;
    }
}