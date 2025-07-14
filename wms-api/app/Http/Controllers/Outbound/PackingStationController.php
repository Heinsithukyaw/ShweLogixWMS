<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\PackingStation;
use App\Models\Outbound\PackingStationAssignment;
use App\Models\Outbound\PackingStationMetrics;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class PackingStationController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of packing stations
     */
    public function index(Request $request): JsonResponse
    {
        $query = PackingStation::with(['warehouse', 'zone', 'currentAssignment.employee']);

        // Apply filters
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('station_type')) {
            $query->where('station_type', $request->station_type);
        }

        $stations = $query->orderBy('station_code')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $stations,
            'message' => 'Packing stations retrieved successfully'
        ]);
    }

    /**
     * Store a newly created packing station
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'station_code' => 'required|string|unique:packing_stations,station_code',
            'station_name' => 'required|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'zone_id' => 'required|exists:zones,id',
            'station_type' => 'required|in:standard,express,bulk,fragile,hazmat',
            'capacity_per_hour' => 'required|integer|min:1',
            'max_concurrent_orders' => 'required|integer|min:1',
            'supported_package_types' => 'required|array',
            'equipment_list' => 'nullable|array',
            'dimensions' => 'required|array',
            'dimensions.length' => 'required|numeric|min:0',
            'dimensions.width' => 'required|numeric|min:0',
            'dimensions.height' => 'required|numeric|min:0',
            'weight_capacity' => 'required|numeric|min:0',
            'power_requirements' => 'nullable|string',
            'network_requirements' => 'nullable|string',
            'safety_equipment' => 'nullable|array',
            'maintenance_schedule' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $station = PackingStation::create([
                'station_code' => $request->station_code,
                'station_name' => $request->station_name,
                'warehouse_id' => $request->warehouse_id,
                'zone_id' => $request->zone_id,
                'station_type' => $request->station_type,
                'status' => 'available',
                'capacity_per_hour' => $request->capacity_per_hour,
                'max_concurrent_orders' => $request->max_concurrent_orders,
                'current_workload' => 0,
                'supported_package_types' => $request->supported_package_types,
                'equipment_list' => $request->equipment_list ?? [],
                'dimensions' => $request->dimensions,
                'weight_capacity' => $request->weight_capacity,
                'power_requirements' => $request->power_requirements,
                'network_requirements' => $request->network_requirements,
                'safety_equipment' => $request->safety_equipment ?? [],
                'maintenance_schedule' => $request->maintenance_schedule,
                'is_active' => $request->is_active ?? true,
                'created_by' => auth()->id()
            ]);

            // Initialize metrics record
            PackingStationMetrics::create([
                'packing_station_id' => $station->id,
                'metric_date' => now()->toDateString(),
                'orders_packed' => 0,
                'items_packed' => 0,
                'total_pack_time' => 0,
                'average_pack_time' => 0,
                'efficiency_rate' => 0,
                'error_count' => 0,
                'downtime_minutes' => 0
            ]);

            // Fire event
            $this->fireTransactionalEvent('outbound.packing_station.created', [
                'station_id' => $station->id,
                'station_code' => $request->station_code,
                'warehouse_id' => $request->warehouse_id,
                'station_type' => $request->station_type,
                'capacity_per_hour' => $request->capacity_per_hour
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $station->load(['warehouse', 'zone']),
                'message' => 'Packing station created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create packing station: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified packing station
     */
    public function show($id): JsonResponse
    {
        $station = PackingStation::with([
            'warehouse',
            'zone',
            'currentAssignment.employee',
            'assignments' => function ($query) {
                $query->orderBy('assigned_at', 'desc')->limit(10);
            },
            'metrics' => function ($query) {
                $query->orderBy('metric_date', 'desc')->limit(30);
            }
        ])->find($id);

        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => 'Packing station not found'
            ], 404);
        }

        // Get current performance metrics
        $currentMetrics = $this->getCurrentPerformanceMetrics($station);

        return response()->json([
            'success' => true,
            'data' => array_merge($station->toArray(), [
                'current_performance' => $currentMetrics
            ]),
            'message' => 'Packing station retrieved successfully'
        ]);
    }

    /**
     * Update the specified packing station
     */
    public function update(Request $request, $id): JsonResponse
    {
        $station = PackingStation::find($id);

        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => 'Packing station not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'station_name' => 'sometimes|string',
            'station_type' => 'sometimes|in:standard,express,bulk,fragile,hazmat',
            'capacity_per_hour' => 'sometimes|integer|min:1',
            'max_concurrent_orders' => 'sometimes|integer|min:1',
            'supported_package_types' => 'sometimes|array',
            'equipment_list' => 'nullable|array',
            'weight_capacity' => 'sometimes|numeric|min:0',
            'power_requirements' => 'nullable|string',
            'network_requirements' => 'nullable|string',
            'safety_equipment' => 'nullable|array',
            'maintenance_schedule' => 'nullable|string',
            'status' => 'sometimes|in:available,occupied,maintenance,offline',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldStatus = $station->status;

            $station->update($request->only([
                'station_name',
                'station_type',
                'capacity_per_hour',
                'max_concurrent_orders',
                'supported_package_types',
                'equipment_list',
                'weight_capacity',
                'power_requirements',
                'network_requirements',
                'safety_equipment',
                'maintenance_schedule',
                'status',
                'is_active'
            ]));

            // Handle status changes
            if ($request->has('status') && $oldStatus !== $request->status) {
                $this->handleStatusChange($station, $oldStatus, $request->status);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $station->load(['warehouse', 'zone', 'currentAssignment.employee']),
                'message' => 'Packing station updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update packing station: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign employee to packing station
     */
    public function assignEmployee(Request $request, $id): JsonResponse
    {
        $station = PackingStation::find($id);

        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => 'Packing station not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i|after:shift_start',
            'assignment_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if employee is already assigned to another station
            $existingAssignment = PackingStationAssignment::where('employee_id', $request->employee_id)
                ->where('assignment_date', $request->assignment_date)
                ->whereNull('unassigned_at')
                ->first();

            if ($existingAssignment) {
                throw new \Exception('Employee is already assigned to another packing station');
            }

            // End current assignment if exists
            $currentAssignment = $station->currentAssignment;
            if ($currentAssignment) {
                $currentAssignment->update([
                    'unassigned_at' => now(),
                    'unassigned_by' => auth()->id()
                ]);
            }

            // Create new assignment
            $assignment = PackingStationAssignment::create([
                'packing_station_id' => $station->id,
                'employee_id' => $request->employee_id,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'assignment_date' => $request->assignment_date,
                'shift_start' => $request->shift_start,
                'shift_end' => $request->shift_end,
                'notes' => $request->notes
            ]);

            // Update station status
            $station->update([
                'status' => 'occupied',
                'current_assignment_id' => $assignment->id
            ]);

            // Fire event
            $this->fireTransactionalEvent('outbound.packing_station.employee_assigned', [
                'station_id' => $station->id,
                'station_code' => $station->station_code,
                'employee_id' => $request->employee_id,
                'assignment_id' => $assignment->id,
                'assigned_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $assignment->load(['packingStation', 'employee', 'assignedBy']),
                'message' => 'Employee assigned to packing station successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign employee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unassign employee from packing station
     */
    public function unassignEmployee(Request $request, $id): JsonResponse
    {
        $station = PackingStation::find($id);

        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => 'Packing station not found'
            ], 404);
        }

        $currentAssignment = $station->currentAssignment;

        if (!$currentAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'No employee currently assigned to this station'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'unassignment_reason' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Calculate assignment metrics
            $assignmentMetrics = $this->calculateAssignmentMetrics($currentAssignment);

            // Update assignment record
            $currentAssignment->update([
                'unassigned_at' => now(),
                'unassigned_by' => auth()->id(),
                'unassignment_reason' => $request->unassignment_reason,
                'final_notes' => $request->notes,
                'performance_metrics' => $assignmentMetrics
            ]);

            // Update station status
            $station->update([
                'status' => 'available',
                'current_assignment_id' => null,
                'current_workload' => 0
            ]);

            // Fire event
            $this->fireTransactionalEvent('outbound.packing_station.employee_unassigned', [
                'station_id' => $station->id,
                'station_code' => $station->station_code,
                'employee_id' => $currentAssignment->employee_id,
                'assignment_id' => $currentAssignment->id,
                'unassigned_by' => auth()->id(),
                'assignment_duration' => $currentAssignment->assigned_at->diffInMinutes(now()),
                'performance_metrics' => $assignmentMetrics
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $currentAssignment->load(['packingStation', 'employee']),
                'message' => 'Employee unassigned from packing station successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to unassign employee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get station performance metrics
     */
    public function metrics(Request $request, $id): JsonResponse
    {
        $station = PackingStation::find($id);

        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => 'Packing station not found'
            ], 404);
        }

        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $metrics = PackingStationMetrics::where('packing_station_id', $id)
            ->whereBetween('metric_date', [$dateFrom, $dateTo])
            ->orderBy('metric_date', 'desc')
            ->get();

        $summary = [
            'total_orders_packed' => $metrics->sum('orders_packed'),
            'total_items_packed' => $metrics->sum('items_packed'),
            'average_pack_time' => $metrics->avg('average_pack_time'),
            'average_efficiency_rate' => $metrics->avg('efficiency_rate'),
            'total_errors' => $metrics->sum('error_count'),
            'total_downtime' => $metrics->sum('downtime_minutes'),
            'utilization_rate' => $this->calculateUtilizationRate($station, $dateFrom, $dateTo),
            'productivity_trend' => $this->getProductivityTrend($metrics)
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'station' => $station,
                'metrics' => $metrics,
                'summary' => $summary
            ],
            'message' => 'Station metrics retrieved successfully'
        ]);
    }

    /**
     * Get packing station analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = PackingStation::query();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $stations = $query->with(['metrics' => function ($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('metric_date', [$dateFrom, $dateTo]);
        }])->get();

        $analytics = [
            'total_stations' => $stations->count(),
            'by_status' => $stations->groupBy('status')->map->count(),
            'by_type' => $stations->groupBy('station_type')->map->count(),
            'average_capacity' => $stations->avg('capacity_per_hour'),
            'total_capacity' => $stations->sum('capacity_per_hour'),
            'utilization_summary' => $this->getUtilizationSummary($stations, $dateFrom, $dateTo),
            'performance_summary' => $this->getPerformanceSummary($stations),
            'top_performing_stations' => $this->getTopPerformingStations($stations),
            'maintenance_schedule' => $this->getMaintenanceSchedule($stations)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Packing station analytics retrieved successfully'
        ]);
    }

    /**
     * Helper methods
     */
    private function handleStatusChange($station, $oldStatus, $newStatus): void
    {
        // Handle status-specific logic
        if ($newStatus === 'maintenance') {
            // Unassign current employee if any
            if ($station->currentAssignment) {
                $station->currentAssignment->update([
                    'unassigned_at' => now(),
                    'unassigned_by' => auth()->id(),
                    'unassignment_reason' => 'Station maintenance'
                ]);
                $station->update(['current_assignment_id' => null]);
            }
        }

        // Fire status change event
        $this->fireTransactionalEvent('outbound.packing_station.status_changed', [
            'station_id' => $station->id,
            'station_code' => $station->station_code,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);
    }

    private function getCurrentPerformanceMetrics($station): array
    {
        $todayMetrics = $station->metrics()
            ->where('metric_date', now()->toDateString())
            ->first();

        if (!$todayMetrics) {
            return [
                'orders_packed_today' => 0,
                'items_packed_today' => 0,
                'current_efficiency' => 0,
                'current_utilization' => 0
            ];
        }

        return [
            'orders_packed_today' => $todayMetrics->orders_packed,
            'items_packed_today' => $todayMetrics->items_packed,
            'current_efficiency' => $todayMetrics->efficiency_rate,
            'current_utilization' => $this->calculateCurrentUtilization($station)
        ];
    }

    private function calculateAssignmentMetrics($assignment): array
    {
        $assignmentDuration = $assignment->assigned_at->diffInMinutes(now());
        
        // Get metrics during assignment period
        $metrics = PackingStationMetrics::where('packing_station_id', $assignment->packing_station_id)
            ->where('metric_date', $assignment->assignment_date)
            ->first();

        return [
            'assignment_duration_minutes' => $assignmentDuration,
            'orders_packed' => $metrics->orders_packed ?? 0,
            'items_packed' => $metrics->items_packed ?? 0,
            'average_pack_time' => $metrics->average_pack_time ?? 0,
            'efficiency_rate' => $metrics->efficiency_rate ?? 0,
            'error_count' => $metrics->error_count ?? 0
        ];
    }

    private function calculateUtilizationRate($station, $dateFrom, $dateTo): float
    {
        $totalMinutes = now()->parse($dateFrom)->diffInMinutes(now()->parse($dateTo));
        $assignmentMinutes = PackingStationAssignment::where('packing_station_id', $station->id)
            ->whereBetween('assignment_date', [$dateFrom, $dateTo])
            ->sum(DB::raw('TIMESTAMPDIFF(MINUTE, assigned_at, COALESCE(unassigned_at, NOW()))'));

        return $totalMinutes > 0 ? ($assignmentMinutes / $totalMinutes) * 100 : 0;
    }

    private function calculateCurrentUtilization($station): float
    {
        if (!$station->currentAssignment) {
            return 0;
        }

        $currentWorkload = $station->current_workload ?? 0;
        $capacity = $station->capacity_per_hour;

        return $capacity > 0 ? ($currentWorkload / $capacity) * 100 : 0;
    }

    private function getProductivityTrend($metrics): array
    {
        return $metrics->map(function ($metric) {
            return [
                'date' => $metric->metric_date,
                'orders_packed' => $metric->orders_packed,
                'efficiency_rate' => $metric->efficiency_rate
            ];
        })->toArray();
    }

    private function getUtilizationSummary($stations, $dateFrom, $dateTo): array
    {
        $totalStations = $stations->count();
        $activeStations = $stations->where('status', 'occupied')->count();
        
        return [
            'total_stations' => $totalStations,
            'active_stations' => $activeStations,
            'utilization_rate' => $totalStations > 0 ? ($activeStations / $totalStations) * 100 : 0,
            'average_capacity_utilization' => $stations->avg(function ($station) {
                return $this->calculateCurrentUtilization($station);
            })
        ];
    }

    private function getPerformanceSummary($stations): array
    {
        $allMetrics = $stations->flatMap->metrics;
        
        return [
            'total_orders_packed' => $allMetrics->sum('orders_packed'),
            'total_items_packed' => $allMetrics->sum('items_packed'),
            'average_efficiency' => $allMetrics->avg('efficiency_rate'),
            'total_errors' => $allMetrics->sum('error_count'),
            'total_downtime' => $allMetrics->sum('downtime_minutes')
        ];
    }

    private function getTopPerformingStations($stations): array
    {
        return $stations->map(function ($station) {
            $metrics = $station->metrics;
            return [
                'station' => $station->only(['id', 'station_code', 'station_name']),
                'total_orders' => $metrics->sum('orders_packed'),
                'average_efficiency' => $metrics->avg('efficiency_rate'),
                'error_rate' => $metrics->sum('error_count') / max($metrics->sum('orders_packed'), 1)
            ];
        })->sortByDesc('average_efficiency')->take(10)->values()->toArray();
    }

    private function getMaintenanceSchedule($stations): array
    {
        return $stations->where('maintenance_schedule', '!=', null)
            ->map(function ($station) {
                return [
                    'station' => $station->only(['id', 'station_code', 'station_name']),
                    'maintenance_schedule' => $station->maintenance_schedule,
                    'last_maintenance' => $station->last_maintenance_date,
                    'next_maintenance' => $station->next_maintenance_date
                ];
            })->values()->toArray();
    }
}