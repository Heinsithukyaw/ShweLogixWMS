<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\LoadPlan;
use App\Models\Outbound\LoadPlanShipment;
use App\Models\Outbound\Shipment;
use App\Models\Vehicle;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class LoadPlanController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of load plans
     */
    public function index(Request $request): JsonResponse
    {
        $query = LoadPlan::with(['vehicle', 'driver', 'warehouse', 'shipments.salesOrder.customer']);

        // Apply filters
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->has('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('load_type')) {
            $query->where('load_type', $request->load_type);
        }

        if ($request->has('planned_departure_from')) {
            $query->where('planned_departure_time', '>=', $request->planned_departure_from);
        }

        if ($request->has('planned_departure_to')) {
            $query->where('planned_departure_time', '<=', $request->planned_departure_to);
        }

        $loadPlans = $query->orderBy('planned_departure_time', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $loadPlans,
            'message' => 'Load plans retrieved successfully'
        ]);
    }

    /**
     * Store a newly created load plan
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:drivers,id',
            'load_type' => 'required|in:delivery,pickup,mixed,transfer',
            'planned_departure_time' => 'required|date|after:now',
            'planned_return_time' => 'nullable|date|after:planned_departure_time',
            'route_optimization' => 'required|in:distance,time,cost,manual',
            'shipments' => 'required|array|min:1',
            'shipments.*' => 'exists:shipments,id',
            'max_weight_override' => 'nullable|numeric|min:0',
            'max_volume_override' => 'nullable|numeric|min:0',
            'special_instructions' => 'nullable|string',
            'requires_signature' => 'boolean',
            'requires_appointment' => 'boolean',
            'temperature_controlled' => 'boolean',
            'hazmat_certified' => 'boolean',
            'priority_level' => 'required|in:low,medium,high,urgent',
            'auto_optimize' => 'boolean'
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

            // Validate vehicle and driver availability
            $this->validateVehicleDriverAvailability($request->vehicle_id, $request->driver_id, $request->planned_departure_time);

            // Validate shipments are ready for loading
            $shipments = Shipment::whereIn('id', $request->shipments)
                ->where('warehouse_id', $request->warehouse_id)
                ->whereIn('status', ['ready_to_ship', 'scheduled'])
                ->get();

            if ($shipments->count() !== count($request->shipments)) {
                throw new \Exception('Some shipments are not ready for loading');
            }

            // Validate load capacity
            $this->validateLoadCapacity($request->vehicle_id, $shipments, $request->all());

            // Generate load plan number
            $loadPlanNumber = $this->generateLoadPlanNumber();

            // Calculate load metrics
            $loadMetrics = $this->calculateLoadMetrics($shipments);

            // Create load plan
            $loadPlan = LoadPlan::create([
                'load_plan_number' => $loadPlanNumber,
                'warehouse_id' => $request->warehouse_id,
                'vehicle_id' => $request->vehicle_id,
                'driver_id' => $request->driver_id,
                'load_type' => $request->load_type,
                'status' => 'planned',
                'planned_departure_time' => $request->planned_departure_time,
                'planned_return_time' => $request->planned_return_time,
                'route_optimization' => $request->route_optimization,
                'max_weight_override' => $request->max_weight_override,
                'max_volume_override' => $request->max_volume_override,
                'special_instructions' => $request->special_instructions,
                'requires_signature' => $request->requires_signature ?? false,
                'requires_appointment' => $request->requires_appointment ?? false,
                'temperature_controlled' => $request->temperature_controlled ?? false,
                'hazmat_certified' => $request->hazmat_certified ?? false,
                'priority_level' => $request->priority_level,
                'total_shipments' => $shipments->count(),
                'total_weight' => $loadMetrics['total_weight'],
                'total_volume' => $loadMetrics['total_volume'],
                'total_value' => $loadMetrics['total_value'],
                'estimated_distance' => 0, // Will be calculated during optimization
                'estimated_duration' => 0, // Will be calculated during optimization
                'created_by' => auth()->id(),
                'load_constraints' => $this->generateLoadConstraints($request->all()),
                'optimization_parameters' => $this->generateOptimizationParameters($request->all())
            ]);

            // Add shipments to load plan
            $this->addShipmentsToLoadPlan($loadPlan, $shipments);

            // Perform route optimization if requested
            if ($request->auto_optimize ?? true) {
                $this->optimizeLoadPlan($loadPlan);
            }

            // Update shipment statuses
            foreach ($shipments as $shipment) {
                $shipment->update(['status' => 'loaded']);
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.load_plan.created', [
                'load_plan_id' => $loadPlan->id,
                'load_plan_number' => $loadPlanNumber,
                'warehouse_id' => $request->warehouse_id,
                'vehicle_id' => $request->vehicle_id,
                'driver_id' => $request->driver_id,
                'shipment_count' => $shipments->count(),
                'total_weight' => $loadMetrics['total_weight'],
                'planned_departure' => $request->planned_departure_time
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $loadPlan->load(['vehicle', 'driver', 'warehouse', 'shipments.salesOrder.customer']),
                'message' => 'Load plan created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create load plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified load plan
     */
    public function show($id): JsonResponse
    {
        $loadPlan = LoadPlan::with([
            'vehicle',
            'driver',
            'warehouse',
            'shipments.salesOrder.customer',
            'shipments.carrier',
            'createdBy'
        ])->find($id);

        if (!$loadPlan) {
            return response()->json([
                'success' => false,
                'message' => 'Load plan not found'
            ], 404);
        }

        // Get optimized route
        $optimizedRoute = $this->getOptimizedRoute($loadPlan);

        // Get load utilization
        $utilization = $this->calculateLoadUtilization($loadPlan);

        return response()->json([
            'success' => true,
            'data' => array_merge($loadPlan->toArray(), [
                'optimized_route' => $optimizedRoute,
                'utilization' => $utilization
            ]),
            'message' => 'Load plan retrieved successfully'
        ]);
    }

    /**
     * Update the specified load plan
     */
    public function update(Request $request, $id): JsonResponse
    {
        $loadPlan = LoadPlan::find($id);

        if (!$loadPlan) {
            return response()->json([
                'success' => false,
                'message' => 'Load plan not found'
            ], 404);
        }

        if (!in_array($loadPlan->status, ['planned', 'optimized'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update load plan in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'driver_id' => 'sometimes|exists:drivers,id',
            'planned_departure_time' => 'sometimes|date',
            'planned_return_time' => 'nullable|date|after:planned_departure_time',
            'special_instructions' => 'nullable|string',
            'priority_level' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:planned,optimized,approved,loading,loaded,dispatched,in_transit,delivered,completed,cancelled'
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

            $oldStatus = $loadPlan->status;
            $oldDriver = $loadPlan->driver_id;

            $loadPlan->update($request->only([
                'driver_id',
                'planned_departure_time',
                'planned_return_time',
                'special_instructions',
                'priority_level',
                'status'
            ]));

            // Handle driver changes
            if ($request->has('driver_id') && $oldDriver !== $request->driver_id) {
                $this->handleDriverChange($loadPlan, $oldDriver, $request->driver_id);
            }

            // Handle status changes
            if ($request->has('status') && $oldStatus !== $request->status) {
                $this->handleStatusChange($loadPlan, $oldStatus, $request->status);
            }

            // Re-optimize if departure time changed
            if ($request->has('planned_departure_time')) {
                $this->optimizeLoadPlan($loadPlan);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $loadPlan->load(['vehicle', 'driver', 'warehouse', 'shipments.salesOrder.customer']),
                'message' => 'Load plan updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update load plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize load plan route
     */
    public function optimize(Request $request, $id): JsonResponse
    {
        $loadPlan = LoadPlan::find($id);

        if (!$loadPlan) {
            return response()->json([
                'success' => false,
                'message' => 'Load plan not found'
            ], 404);
        }

        if (!in_array($loadPlan->status, ['planned', 'optimized'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot optimize load plan in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'optimization_type' => 'required|in:distance,time,cost,fuel_efficiency',
            'constraints' => 'nullable|array',
            'force_reoptimize' => 'boolean'
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

            // Perform optimization
            $optimizationResult = $this->performRouteOptimization(
                $loadPlan,
                $request->optimization_type,
                $request->constraints ?? [],
                $request->force_reoptimize ?? false
            );

            // Update load plan with optimization results
            $loadPlan->update([
                'status' => 'optimized',
                'route_optimization' => $request->optimization_type,
                'estimated_distance' => $optimizationResult['total_distance'],
                'estimated_duration' => $optimizationResult['total_duration'],
                'optimization_data' => $optimizationResult,
                'optimized_at' => now(),
                'optimized_by' => auth()->id()
            ]);

            // Update shipment delivery sequence
            $this->updateShipmentSequence($loadPlan, $optimizationResult['delivery_sequence']);

            // Fire event
            $this->fireTransactionalEvent('outbound.load_plan.optimized', [
                'load_plan_id' => $loadPlan->id,
                'load_plan_number' => $loadPlan->load_plan_number,
                'optimization_type' => $request->optimization_type,
                'distance_saved' => $optimizationResult['distance_improvement'],
                'time_saved' => $optimizationResult['time_improvement'],
                'optimized_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'load_plan' => $loadPlan->load(['vehicle', 'driver', 'warehouse']),
                    'optimization_result' => $optimizationResult
                ],
                'message' => 'Load plan optimized successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize load plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dispatch load plan
     */
    public function dispatch(Request $request, $id): JsonResponse
    {
        $loadPlan = LoadPlan::find($id);

        if (!$loadPlan) {
            return response()->json([
                'success' => false,
                'message' => 'Load plan not found'
            ], 404);
        }

        if ($loadPlan->status !== 'loaded') {
            return response()->json([
                'success' => false,
                'message' => 'Load plan must be loaded before dispatch'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'actual_departure_time' => 'nullable|date',
            'dispatch_notes' => 'nullable|string',
            'fuel_level' => 'nullable|numeric|min:0|max:100',
            'odometer_reading' => 'nullable|integer|min:0'
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

            $loadPlan->update([
                'status' => 'dispatched',
                'actual_departure_time' => $request->actual_departure_time ?? now(),
                'dispatch_notes' => $request->dispatch_notes,
                'fuel_level_at_dispatch' => $request->fuel_level,
                'odometer_at_dispatch' => $request->odometer_reading,
                'dispatched_by' => auth()->id(),
                'dispatched_at' => now()
            ]);

            // Update shipment statuses
            foreach ($loadPlan->shipments as $shipment) {
                $shipment->update(['status' => 'in_transit']);
            }

            // Create tracking records
            $this->createDispatchTrackingRecords($loadPlan);

            // Send notifications
            $this->sendDispatchNotifications($loadPlan);

            // Fire event
            $this->fireTransactionalEvent('outbound.load_plan.dispatched', [
                'load_plan_id' => $loadPlan->id,
                'load_plan_number' => $loadPlan->load_plan_number,
                'vehicle_id' => $loadPlan->vehicle_id,
                'driver_id' => $loadPlan->driver_id,
                'actual_departure_time' => $loadPlan->actual_departure_time,
                'dispatched_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $loadPlan->load(['vehicle', 'driver', 'warehouse', 'shipments']),
                'message' => 'Load plan dispatched successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch load plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get load plan analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = LoadPlan::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $analytics = [
            'total_load_plans' => $query->count(),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_load_type' => $query->groupBy('load_type')->selectRaw('load_type, count(*) as count')->pluck('count', 'load_type'),
            'by_vehicle' => $this->getLoadPlansByVehicle($query),
            'utilization_metrics' => $this->getUtilizationMetrics($query),
            'efficiency_metrics' => $this->getEfficiencyMetrics($query),
            'cost_analysis' => $this->getCostAnalysis($query),
            'performance_trends' => $this->getPerformanceTrends($dateFrom, $dateTo, $warehouseId)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Load plan analytics retrieved successfully'
        ]);
    }

    /**
     * Helper methods
     */
    private function generateLoadPlanNumber(): string
    {
        $year = date('Y');
        $sequence = LoadPlan::whereYear('created_at', $year)->count() + 1;
        
        return 'LP-' . $year . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    private function validateVehicleDriverAvailability($vehicleId, $driverId, $departureTime): void
    {
        // Check vehicle availability
        $vehicleConflict = LoadPlan::where('vehicle_id', $vehicleId)
            ->whereIn('status', ['planned', 'optimized', 'approved', 'loading', 'loaded', 'dispatched', 'in_transit'])
            ->where('planned_departure_time', '<=', $departureTime)
            ->where('planned_return_time', '>=', $departureTime)
            ->exists();

        if ($vehicleConflict) {
            throw new \Exception('Vehicle is not available at the specified time');
        }

        // Check driver availability
        $driverConflict = LoadPlan::where('driver_id', $driverId)
            ->whereIn('status', ['planned', 'optimized', 'approved', 'loading', 'loaded', 'dispatched', 'in_transit'])
            ->where('planned_departure_time', '<=', $departureTime)
            ->where('planned_return_time', '>=', $departureTime)
            ->exists();

        if ($driverConflict) {
            throw new \Exception('Driver is not available at the specified time');
        }
    }

    private function validateLoadCapacity($vehicleId, $shipments, $criteria): void
    {
        $vehicle = Vehicle::find($vehicleId);
        
        $totalWeight = $shipments->sum('weight');
        $totalVolume = $shipments->sum('volume');

        $maxWeight = $criteria['max_weight_override'] ?? $vehicle->max_weight;
        $maxVolume = $criteria['max_volume_override'] ?? $vehicle->max_volume;

        if ($totalWeight > $maxWeight) {
            throw new \Exception('Total weight exceeds vehicle capacity');
        }

        if ($totalVolume > $maxVolume) {
            throw new \Exception('Total volume exceeds vehicle capacity');
        }
    }

    private function calculateLoadMetrics($shipments): array
    {
        return [
            'total_weight' => $shipments->sum('weight'),
            'total_volume' => $shipments->sum('volume'),
            'total_value' => $shipments->sum('declared_value')
        ];
    }

    private function generateLoadConstraints($criteria): array
    {
        return [
            'requires_signature' => $criteria['requires_signature'] ?? false,
            'requires_appointment' => $criteria['requires_appointment'] ?? false,
            'temperature_controlled' => $criteria['temperature_controlled'] ?? false,
            'hazmat_certified' => $criteria['hazmat_certified'] ?? false,
            'max_weight_override' => $criteria['max_weight_override'],
            'max_volume_override' => $criteria['max_volume_override']
        ];
    }

    private function generateOptimizationParameters($criteria): array
    {
        return [
            'route_optimization' => $criteria['route_optimization'],
            'priority_level' => $criteria['priority_level'],
            'auto_optimize' => $criteria['auto_optimize'] ?? true,
            'optimization_factors' => [
                'distance_weight' => 0.4,
                'time_weight' => 0.3,
                'cost_weight' => 0.2,
                'priority_weight' => 0.1
            ]
        ];
    }

    private function addShipmentsToLoadPlan($loadPlan, $shipments): void
    {
        $sequence = 1;
        
        foreach ($shipments as $shipment) {
            LoadPlanShipment::create([
                'load_plan_id' => $loadPlan->id,
                'shipment_id' => $shipment->id,
                'delivery_sequence' => $sequence++,
                'estimated_delivery_time' => null, // Will be set during optimization
                'special_instructions' => $shipment->special_instructions,
                'requires_signature' => $shipment->signature_required,
                'delivery_priority' => $shipment->priority_level ?? 'medium'
            ]);
        }
    }

    private function optimizeLoadPlan($loadPlan): void
    {
        $optimizationResult = $this->performRouteOptimization(
            $loadPlan,
            $loadPlan->route_optimization,
            [],
            false
        );

        $loadPlan->update([
            'status' => 'optimized',
            'estimated_distance' => $optimizationResult['total_distance'],
            'estimated_duration' => $optimizationResult['total_duration'],
            'optimization_data' => $optimizationResult,
            'optimized_at' => now()
        ]);

        $this->updateShipmentSequence($loadPlan, $optimizationResult['delivery_sequence']);
    }

    private function getOptimizedRoute($loadPlan): array
    {
        return $loadPlan->shipments()
            ->with(['salesOrder.customer'])
            ->orderBy('load_plan_shipments.delivery_sequence')
            ->get()
            ->map(function ($shipment) {
                return [
                    'sequence' => $shipment->pivot->delivery_sequence,
                    'shipment' => $shipment,
                    'customer' => $shipment->salesOrder->customer,
                    'delivery_address' => $shipment->ship_to_address,
                    'estimated_delivery_time' => $shipment->pivot->estimated_delivery_time,
                    'special_instructions' => $shipment->pivot->special_instructions
                ];
            })
            ->toArray();
    }

    private function calculateLoadUtilization($loadPlan): array
    {
        $vehicle = $loadPlan->vehicle;
        
        return [
            'weight_utilization' => $vehicle->max_weight > 0 
                ? ($loadPlan->total_weight / $vehicle->max_weight) * 100 
                : 0,
            'volume_utilization' => $vehicle->max_volume > 0 
                ? ($loadPlan->total_volume / $vehicle->max_volume) * 100 
                : 0,
            'capacity_efficiency' => $this->calculateCapacityEfficiency($loadPlan),
            'load_factor' => $this->calculateLoadFactor($loadPlan)
        ];
    }

    private function calculateCapacityEfficiency($loadPlan): float
    {
        $vehicle = $loadPlan->vehicle;
        $weightUtil = $vehicle->max_weight > 0 ? ($loadPlan->total_weight / $vehicle->max_weight) : 0;
        $volumeUtil = $vehicle->max_volume > 0 ? ($loadPlan->total_volume / $vehicle->max_volume) : 0;
        
        return max($weightUtil, $volumeUtil) * 100;
    }

    private function calculateLoadFactor($loadPlan): float
    {
        // Load factor considers both weight and volume utilization
        $vehicle = $loadPlan->vehicle;
        $weightUtil = $vehicle->max_weight > 0 ? ($loadPlan->total_weight / $vehicle->max_weight) : 0;
        $volumeUtil = $vehicle->max_volume > 0 ? ($loadPlan->total_volume / $vehicle->max_volume) : 0;
        
        return (($weightUtil + $volumeUtil) / 2) * 100;
    }

    private function handleDriverChange($loadPlan, $oldDriver, $newDriver): void
    {
        // Validate new driver availability
        $this->validateVehicleDriverAvailability($loadPlan->vehicle_id, $newDriver, $loadPlan->planned_departure_time);

        // Fire driver change event
        $this->fireTransactionalEvent('outbound.load_plan.driver_changed', [
            'load_plan_id' => $loadPlan->id,
            'load_plan_number' => $loadPlan->load_plan_number,
            'old_driver_id' => $oldDriver,
            'new_driver_id' => $newDriver,
            'changed_by' => auth()->id()
        ]);
    }

    private function handleStatusChange($loadPlan, $oldStatus, $newStatus): void
    {
        // Handle status-specific logic
        $statusTimestamps = [
            'approved' => ['approved_at' => now(), 'approved_by' => auth()->id()],
            'loading' => ['loading_started_at' => now()],
            'loaded' => ['loading_completed_at' => now()],
            'dispatched' => ['dispatched_at' => now(), 'dispatched_by' => auth()->id()],
            'in_transit' => ['in_transit_since' => now()],
            'delivered' => ['delivered_at' => now()],
            'completed' => ['completed_at' => now()],
            'cancelled' => ['cancelled_at' => now(), 'cancelled_by' => auth()->id()]
        ];

        if (isset($statusTimestamps[$newStatus])) {
            $loadPlan->update($statusTimestamps[$newStatus]);
        }

        // Update shipment statuses based on load plan status
        if (in_array($newStatus, ['dispatched', 'in_transit'])) {
            foreach ($loadPlan->shipments as $shipment) {
                $shipment->update(['status' => 'in_transit']);
            }
        }

        // Fire status change event
        $this->fireTransactionalEvent('outbound.load_plan.status_changed', [
            'load_plan_id' => $loadPlan->id,
            'load_plan_number' => $loadPlan->load_plan_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);
    }

    private function performRouteOptimization($loadPlan, $optimizationType, $constraints, $forceReoptimize): array
    {
        // This would integrate with route optimization algorithms
        // For now, return a simplified optimization result
        
        $shipments = $loadPlan->shipments;
        $optimizedSequence = $this->optimizeDeliverySequence($shipments, $optimizationType);
        
        return [
            'optimization_type' => $optimizationType,
            'total_distance' => 250.5, // km
            'total_duration' => 480, // minutes
            'fuel_consumption' => 35.2, // liters
            'estimated_cost' => 125.50,
            'delivery_sequence' => $optimizedSequence,
            'distance_improvement' => 15.3, // km saved
            'time_improvement' => 25, // minutes saved
            'optimization_score' => 85,
            'optimization_timestamp' => now()
        ];
    }

    private function optimizeDeliverySequence($shipments, $optimizationType): array
    {
        // Simplified optimization logic
        switch ($optimizationType) {
            case 'distance':
                return $this->optimizeByDistance($shipments);
            case 'time':
                return $this->optimizeByTime($shipments);
            case 'cost':
                return $this->optimizeByCost($shipments);
            default:
                return $this->optimizeByDistance($shipments);
        }
    }

    private function optimizeByDistance($shipments): array
    {
        // Simplified distance-based optimization
        return $shipments->sortBy('ship_to_address.postal_code')->values()->map(function ($shipment, $index) {
            return [
                'shipment_id' => $shipment->id,
                'sequence' => $index + 1,
                'estimated_arrival' => now()->addMinutes(($index + 1) * 45),
                'distance_from_previous' => ($index + 1) * 15.5
            ];
        })->toArray();
    }

    private function optimizeByTime($shipments): array
    {
        // Simplified time-based optimization
        return $shipments->sortBy('delivery_time_window')->values()->map(function ($shipment, $index) {
            return [
                'shipment_id' => $shipment->id,
                'sequence' => $index + 1,
                'estimated_arrival' => now()->addMinutes(($index + 1) * 40),
                'distance_from_previous' => ($index + 1) * 18.2
            ];
        })->toArray();
    }

    private function optimizeByCost($shipments): array
    {
        // Simplified cost-based optimization
        return $shipments->sortBy('shipping_cost')->values()->map(function ($shipment, $index) {
            return [
                'shipment_id' => $shipment->id,
                'sequence' => $index + 1,
                'estimated_arrival' => now()->addMinutes(($index + 1) * 50),
                'distance_from_previous' => ($index + 1) * 16.8
            ];
        })->toArray();
    }

    private function updateShipmentSequence($loadPlan, $deliverySequence): void
    {
        foreach ($deliverySequence as $delivery) {
            LoadPlanShipment::where('load_plan_id', $loadPlan->id)
                ->where('shipment_id', $delivery['shipment_id'])
                ->update([
                    'delivery_sequence' => $delivery['sequence'],
                    'estimated_delivery_time' => $delivery['estimated_arrival']
                ]);
        }
    }

    private function createDispatchTrackingRecords($loadPlan): void
    {
        // Create initial tracking records for all shipments
        foreach ($loadPlan->shipments as $shipment) {
            // This would create tracking records in a shipment_tracking table
        }
    }

    private function sendDispatchNotifications($loadPlan): void
    {
        // Send notifications to customers, driver, and warehouse staff
        // This would integrate with your notification system
    }

    /**
     * Analytics helper methods
     */
    private function getLoadPlansByVehicle($query): array
    {
        return $query->with('vehicle')
            ->selectRaw('vehicle_id, count(*) as load_count, avg(total_weight) as avg_weight, avg(estimated_distance) as avg_distance')
            ->groupBy('vehicle_id')
            ->get()
            ->map(function ($item) {
                return [
                    'vehicle' => $item->vehicle,
                    'load_count' => $item->load_count,
                    'avg_weight' => round($item->avg_weight, 2),
                    'avg_distance' => round($item->avg_distance, 2)
                ];
            })
            ->toArray();
    }

    private function getUtilizationMetrics($query): array
    {
        $loadPlans = $query->with('vehicle')->get();
        
        $totalUtilization = 0;
        $count = 0;
        
        foreach ($loadPlans as $loadPlan) {
            if ($loadPlan->vehicle && $loadPlan->vehicle->max_weight > 0) {
                $utilization = ($loadPlan->total_weight / $loadPlan->vehicle->max_weight) * 100;
                $totalUtilization += $utilization;
                $count++;
            }
        }
        
        return [
            'average_weight_utilization' => $count > 0 ? $totalUtilization / $count : 0,
            'high_utilization_loads' => $loadPlans->filter(function ($lp) {
                return $lp->vehicle && $lp->vehicle->max_weight > 0 && 
                       (($lp->total_weight / $lp->vehicle->max_weight) * 100) >= 80;
            })->count(),
            'low_utilization_loads' => $loadPlans->filter(function ($lp) {
                return $lp->vehicle && $lp->vehicle->max_weight > 0 && 
                       (($lp->total_weight / $lp->vehicle->max_weight) * 100) < 50;
            })->count()
        ];
    }

    private function getEfficiencyMetrics($query): array
    {
        $completed = $query->where('status', 'completed');
        
        return [
            'completion_rate' => $query->count() > 0 ? ($completed->count() / $query->count()) * 100 : 0,
            'on_time_delivery_rate' => $this->calculateOnTimeDeliveryRate($completed),
            'average_optimization_improvement' => $this->calculateAverageOptimizationImprovement($completed),
            'fuel_efficiency' => $this->calculateFuelEfficiency($completed)
        ];
    }

    private function calculateOnTimeDeliveryRate($query): float
    {
        // This would calculate actual on-time delivery performance
        return 92.5; // Placeholder
    }

    private function calculateAverageOptimizationImprovement($query): float
    {
        return $query->whereNotNull('optimization_data')->get()->avg(function ($loadPlan) {
            $data = $loadPlan->optimization_data;
            return $data['distance_improvement'] ?? 0;
        }) ?? 0;
    }

    private function calculateFuelEfficiency($query): float
    {
        // This would calculate fuel efficiency metrics
        return 8.5; // km per liter placeholder
    }

    private function getCostAnalysis($query): array
    {
        return [
            'total_transportation_cost' => 15250.00, // Placeholder
            'cost_per_kilometer' => 0.85,
            'cost_per_shipment' => 12.50,
            'fuel_cost_percentage' => 35.2,
            'labor_cost_percentage' => 45.8,
            'vehicle_cost_percentage' => 19.0
        ];
    }

    private function getPerformanceTrends($dateFrom, $dateTo, $warehouseId): array
    {
        $query = LoadPlan::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, count(*) as load_count, avg(total_weight) as avg_weight, avg(estimated_distance) as avg_distance')
            ->groupBy('date')
            ->orderBy('date');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->toArray();
    }
}