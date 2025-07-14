<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\ZonePick;
use App\Models\Outbound\ZonePickAssignment;
use App\Models\Outbound\ZonePickItem;
use App\Models\Zone;
use App\Models\SalesOrder;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class ZonePickController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of zone picks
     */
    public function index(Request $request): JsonResponse
    {
        $query = ZonePick::with(['warehouse', 'zone', 'assignments.picker', 'orders.customer']);

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

        if ($request->has('pick_strategy')) {
            $query->where('pick_strategy', $request->pick_strategy);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $zonePicks = $query->orderBy('priority_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $zonePicks,
            'message' => 'Zone picks retrieved successfully'
        ]);
    }

    /**
     * Store a newly created zone pick
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'zone_id' => 'required|exists:zones,id',
            'pick_strategy' => 'required|in:sequential,parallel,priority_based,distance_optimized',
            'orders' => 'required|array|min:1',
            'orders.*' => 'exists:sales_orders,id',
            'max_pickers' => 'required|integer|min:1|max:10',
            'priority_threshold' => 'nullable|integer|min:0|max:100',
            'pick_deadline' => 'nullable|date|after:now',
            'special_instructions' => 'nullable|string',
            'auto_assign_pickers' => 'boolean'
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

            // Validate orders are in the specified zone and ready for picking
            $orders = SalesOrder::whereIn('id', $request->orders)
                ->where('warehouse_id', $request->warehouse_id)
                ->whereIn('status', ['allocated', 'released'])
                ->get();

            if ($orders->count() !== count($request->orders)) {
                throw new \Exception('Some orders are not eligible for zone picking');
            }

            // Validate orders have items in the specified zone
            $this->validateOrdersInZone($orders, $request->zone_id);

            // Generate zone pick number
            $zonePickNumber = $this->generateZonePickNumber();

            // Calculate zone pick metrics
            $metrics = $this->calculateZonePickMetrics($orders, $request->zone_id);

            // Create zone pick
            $zonePick = ZonePick::create([
                'zone_pick_number' => $zonePickNumber,
                'warehouse_id' => $request->warehouse_id,
                'zone_id' => $request->zone_id,
                'pick_strategy' => $request->pick_strategy,
                'status' => 'created',
                'total_orders' => $orders->count(),
                'total_items' => $metrics['total_items'],
                'total_quantity' => $metrics['total_quantity'],
                'max_pickers' => $request->max_pickers,
                'priority_threshold' => $request->priority_threshold ?? 0,
                'priority_score' => $this->calculatePriorityScore($orders),
                'pick_deadline' => $request->pick_deadline,
                'estimated_pick_time' => $this->estimatePickTime($metrics),
                'special_instructions' => $request->special_instructions,
                'auto_assign_pickers' => $request->auto_assign_pickers ?? false,
                'created_by' => auth()->id(),
                'pick_path_optimization' => $this->generatePickPathOptimization($orders, $request->zone_id, $request->pick_strategy)
            ]);

            // Add orders to zone pick
            foreach ($orders as $order) {
                $zonePick->orders()->attach($order->id, [
                    'original_status' => $order->status,
                    'priority_score' => $order->priority_score ?? 0,
                    'estimated_pick_time' => $this->estimateOrderPickTime($order, $request->zone_id),
                    'item_count' => $this->getOrderItemsInZone($order, $request->zone_id)->count()
                ]);

                // Update order status
                $order->update(['status' => 'zone_picking']);
            }

            // Generate zone pick items
            $this->generateZonePickItems($zonePick);

            // Auto-assign pickers if requested
            if ($request->auto_assign_pickers) {
                $this->autoAssignPickers($zonePick);
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.zone_pick.created', [
                'zone_pick_id' => $zonePick->id,
                'zone_pick_number' => $zonePickNumber,
                'warehouse_id' => $request->warehouse_id,
                'zone_id' => $request->zone_id,
                'pick_strategy' => $request->pick_strategy,
                'order_count' => $orders->count(),
                'total_items' => $metrics['total_items']
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $zonePick->load(['warehouse', 'zone', 'orders.customer']),
                'message' => 'Zone pick created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create zone pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified zone pick
     */
    public function show($id): JsonResponse
    {
        $zonePick = ZonePick::with([
            'warehouse',
            'zone',
            'orders.customer',
            'assignments.picker',
            'items.product',
            'items.location',
            'createdBy'
        ])->find($id);

        if (!$zonePick) {
            return response()->json([
                'success' => false,
                'message' => 'Zone pick not found'
            ], 404);
        }

        // Get optimized pick path
        $pickPath = $this->getOptimizedPickPath($zonePick);

        // Get progress metrics
        $progress = $this->calculateProgress($zonePick);

        return response()->json([
            'success' => true,
            'data' => array_merge($zonePick->toArray(), [
                'pick_path' => $pickPath,
                'progress' => $progress
            ]),
            'message' => 'Zone pick retrieved successfully'
        ]);
    }

    /**
     * Update the specified zone pick
     */
    public function update(Request $request, $id): JsonResponse
    {
        $zonePick = ZonePick::find($id);

        if (!$zonePick) {
            return response()->json([
                'success' => false,
                'message' => 'Zone pick not found'
            ], 404);
        }

        if (!in_array($zonePick->status, ['created', 'assigned', 'in_progress'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update zone pick in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'pick_strategy' => 'sometimes|in:sequential,parallel,priority_based,distance_optimized',
            'max_pickers' => 'sometimes|integer|min:1|max:10',
            'priority_threshold' => 'nullable|integer|min:0|max:100',
            'pick_deadline' => 'nullable|date',
            'special_instructions' => 'nullable|string',
            'status' => 'sometimes|in:created,assigned,in_progress,completed,cancelled'
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

            $oldStatus = $zonePick->status;
            $oldStrategy = $zonePick->pick_strategy;

            $zonePick->update($request->only([
                'pick_strategy',
                'max_pickers',
                'priority_threshold',
                'pick_deadline',
                'special_instructions',
                'status'
            ]));

            // Regenerate pick path if strategy changed
            if ($request->has('pick_strategy') && $oldStrategy !== $request->pick_strategy) {
                $this->regeneratePickPath($zonePick, $request->pick_strategy);
            }

            // Handle status changes
            if ($request->has('status') && $oldStatus !== $request->status) {
                $this->handleStatusChange($zonePick, $oldStatus, $request->status);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $zonePick->load(['warehouse', 'zone', 'assignments.picker']),
                'message' => 'Zone pick updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update zone pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign pickers to zone pick
     */
    public function assignPickers(Request $request, $id): JsonResponse
    {
        $zonePick = ZonePick::find($id);

        if (!$zonePick) {
            return response()->json([
                'success' => false,
                'message' => 'Zone pick not found'
            ], 404);
        }

        if ($zonePick->status !== 'created') {
            return response()->json([
                'success' => false,
                'message' => 'Zone pick must be in created status to assign pickers'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'assignments' => 'required|array|min:1',
            'assignments.*.picker_id' => 'required|exists:employees,id',
            'assignments.*.assignment_type' => 'required|in:primary,secondary,support',
            'assignments.*.assigned_items' => 'nullable|array',
            'assignments.*.assigned_items.*' => 'exists:zone_pick_items,id',
            'assignments.*.notes' => 'nullable|string'
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

            // Validate picker count doesn't exceed maximum
            if (count($request->assignments) > $zonePick->max_pickers) {
                throw new \Exception('Number of assigned pickers exceeds maximum allowed');
            }

            // Clear existing assignments
            ZonePickAssignment::where('zone_pick_id', $id)->delete();

            // Create new assignments
            foreach ($request->assignments as $assignment) {
                $zonePickAssignment = ZonePickAssignment::create([
                    'zone_pick_id' => $id,
                    'picker_id' => $assignment['picker_id'],
                    'assignment_type' => $assignment['assignment_type'],
                    'status' => 'assigned',
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                    'notes' => $assignment['notes'] ?? null
                ]);

                // Assign specific items if provided
                if (isset($assignment['assigned_items']) && !empty($assignment['assigned_items'])) {
                    ZonePickItem::whereIn('id', $assignment['assigned_items'])
                        ->update(['assigned_picker_id' => $assignment['picker_id']]);
                }
            }

            // Update zone pick status
            $zonePick->update([
                'status' => 'assigned',
                'assigned_at' => now(),
                'assigned_pickers' => count($request->assignments)
            ]);

            // Fire event
            $this->fireTransactionalEvent('outbound.zone_pick.pickers_assigned', [
                'zone_pick_id' => $zonePick->id,
                'zone_pick_number' => $zonePick->zone_pick_number,
                'picker_count' => count($request->assignments),
                'picker_ids' => collect($request->assignments)->pluck('picker_id')->toArray(),
                'assigned_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $zonePick->load(['warehouse', 'zone', 'assignments.picker']),
                'message' => 'Pickers assigned successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign pickers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start zone pick
     */
    public function start(Request $request, $id): JsonResponse
    {
        $zonePick = ZonePick::find($id);

        if (!$zonePick) {
            return response()->json([
                'success' => false,
                'message' => 'Zone pick not found'
            ], 404);
        }

        if ($zonePick->status !== 'assigned') {
            return response()->json([
                'success' => false,
                'message' => 'Zone pick must be assigned before starting'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $zonePick->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'started_by' => auth()->id()
            ]);

            // Update assignment statuses
            $zonePick->assignments()->update([
                'status' => 'in_progress',
                'started_at' => now()
            ]);

            // Create individual pick tasks for mobile devices
            $this->createPickTasks($zonePick);

            // Fire event
            $this->fireTransactionalEvent('outbound.zone_pick.started', [
                'zone_pick_id' => $zonePick->id,
                'zone_pick_number' => $zonePick->zone_pick_number,
                'started_by' => auth()->id(),
                'picker_count' => $zonePick->assignments->count()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $zonePick->load(['warehouse', 'zone', 'assignments.picker']),
                'message' => 'Zone pick started successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to start zone pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete zone pick
     */
    public function complete(Request $request, $id): JsonResponse
    {
        $zonePick = ZonePick::find($id);

        if (!$zonePick) {
            return response()->json([
                'success' => false,
                'message' => 'Zone pick not found'
            ], 404);
        }

        if ($zonePick->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Zone pick is not in progress'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'completion_notes' => 'nullable|string',
            'exceptions' => 'nullable|array',
            'exceptions.*.item_id' => 'required|exists:zone_pick_items,id',
            'exceptions.*.exception_type' => 'required|in:short_pick,damaged,not_found,substitution',
            'exceptions.*.quantity_picked' => 'required|integer|min:0',
            'exceptions.*.notes' => 'nullable|string'
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

            // Process exceptions if any
            if ($request->has('exceptions')) {
                $this->processPickExceptions($zonePick, $request->exceptions);
            }

            // Calculate completion metrics
            $completionMetrics = $this->calculateCompletionMetrics($zonePick);

            $zonePick->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'completion_notes' => $request->completion_notes,
                'actual_pick_time' => $zonePick->started_at ? now()->diffInMinutes($zonePick->started_at) : null,
                'completion_metrics' => $completionMetrics
            ]);

            // Update assignment statuses
            $zonePick->assignments()->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // Update order statuses
            $this->updateOrderStatuses($zonePick);

            // Fire event
            $this->fireTransactionalEvent('outbound.zone_pick.completed', [
                'zone_pick_id' => $zonePick->id,
                'zone_pick_number' => $zonePick->zone_pick_number,
                'completed_by' => auth()->id(),
                'actual_pick_time' => $zonePick->actual_pick_time,
                'completion_metrics' => $completionMetrics
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $zonePick->load(['warehouse', 'zone', 'assignments.picker']),
                'message' => 'Zone pick completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete zone pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get zone pick analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $zoneId = $request->get('zone_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = ZonePick::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($zoneId) {
            $query->where('zone_id', $zoneId);
        }

        $analytics = [
            'total_zone_picks' => $query->count(),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_strategy' => $query->groupBy('pick_strategy')->selectRaw('pick_strategy, count(*) as count')->pluck('count', 'pick_strategy'),
            'by_zone' => $this->getZonePicksByZone($query),
            'average_pick_time' => $query->whereNotNull('actual_pick_time')->avg('actual_pick_time'),
            'average_items_per_pick' => $query->avg('total_items'),
            'picker_performance' => $this->getPickerPerformance($query),
            'efficiency_metrics' => $this->getEfficiencyMetrics($query),
            'zone_utilization' => $this->getZoneUtilization($query),
            'pick_trends' => $this->getPickTrends($dateFrom, $dateTo, $warehouseId, $zoneId)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Zone pick analytics retrieved successfully'
        ]);
    }

    /**
     * Helper methods
     */
    private function generateZonePickNumber(): string
    {
        $year = date('Y');
        $sequence = ZonePick::whereYear('created_at', $year)->count() + 1;
        
        return 'ZP-' . $year . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    private function validateOrdersInZone($orders, $zoneId): void
    {
        foreach ($orders as $order) {
            $itemsInZone = $this->getOrderItemsInZone($order, $zoneId);
            if ($itemsInZone->isEmpty()) {
                throw new \Exception("Order {$order->order_number} has no items in the specified zone");
            }
        }
    }

    private function getOrderItemsInZone($order, $zoneId)
    {
        return $order->items()->whereHas('location', function ($query) use ($zoneId) {
            $query->where('zone_id', $zoneId);
        })->get();
    }

    private function calculateZonePickMetrics($orders, $zoneId): array
    {
        $totalItems = 0;
        $totalQuantity = 0;

        foreach ($orders as $order) {
            $itemsInZone = $this->getOrderItemsInZone($order, $zoneId);
            $totalItems += $itemsInZone->count();
            $totalQuantity += $itemsInZone->sum('quantity');
        }

        return [
            'total_items' => $totalItems,
            'total_quantity' => $totalQuantity
        ];
    }

    private function calculatePriorityScore($orders): int
    {
        return $orders->avg('priority_score') ?? 50;
    }

    private function estimatePickTime($metrics): int
    {
        // Estimate based on items and quantity
        $baseTime = 10; // Base time in minutes
        $itemTime = $metrics['total_items'] * 0.5; // 30 seconds per item
        $quantityTime = $metrics['total_quantity'] * 0.1; // 6 seconds per unit
        
        return (int) ($baseTime + $itemTime + $quantityTime);
    }

    private function estimateOrderPickTime($order, $zoneId): int
    {
        $itemsInZone = $this->getOrderItemsInZone($order, $zoneId);
        return $itemsInZone->count() * 0.5; // 30 seconds per item
    }

    private function generatePickPathOptimization($orders, $zoneId, $strategy): array
    {
        // Generate optimized pick path based on strategy
        $optimization = [
            'strategy' => $strategy,
            'zone_id' => $zoneId,
            'total_distance' => 0,
            'path_sequence' => []
        ];

        switch ($strategy) {
            case 'sequential':
                $optimization['path_sequence'] = $this->generateSequentialPath($orders, $zoneId);
                break;
            case 'distance_optimized':
                $optimization['path_sequence'] = $this->generateDistanceOptimizedPath($orders, $zoneId);
                break;
            case 'priority_based':
                $optimization['path_sequence'] = $this->generatePriorityBasedPath($orders, $zoneId);
                break;
            default:
                $optimization['path_sequence'] = $this->generateDefaultPath($orders, $zoneId);
        }

        return $optimization;
    }

    private function generateSequentialPath($orders, $zoneId): array
    {
        // Generate sequential pick path
        $path = [];
        foreach ($orders as $order) {
            $items = $this->getOrderItemsInZone($order, $zoneId);
            foreach ($items as $item) {
                $path[] = [
                    'order_id' => $order->id,
                    'item_id' => $item->id,
                    'location_id' => $item->location_id,
                    'sequence' => count($path) + 1
                ];
            }
        }
        return $path;
    }

    private function generateDistanceOptimizedPath($orders, $zoneId): array
    {
        // This would implement distance optimization algorithms
        // For now, return sequential path
        return $this->generateSequentialPath($orders, $zoneId);
    }

    private function generatePriorityBasedPath($orders, $zoneId): array
    {
        // Sort orders by priority and generate path
        $sortedOrders = $orders->sortByDesc('priority_score');
        return $this->generateSequentialPath($sortedOrders, $zoneId);
    }

    private function generateDefaultPath($orders, $zoneId): array
    {
        return $this->generateSequentialPath($orders, $zoneId);
    }

    private function generateZonePickItems($zonePick): void
    {
        $sequence = 1;
        
        foreach ($zonePick->orders as $order) {
            $itemsInZone = $this->getOrderItemsInZone($order, $zonePick->zone_id);
            
            foreach ($itemsInZone as $orderItem) {
                ZonePickItem::create([
                    'zone_pick_id' => $zonePick->id,
                    'sales_order_id' => $order->id,
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'location_id' => $orderItem->location_id,
                    'sequence_number' => $sequence++,
                    'required_quantity' => $orderItem->quantity,
                    'picked_quantity' => 0,
                    'status' => 'pending',
                    'priority_score' => $order->priority_score ?? 0
                ]);
            }
        }
    }

    private function autoAssignPickers($zonePick): void
    {
        // Auto-assign available pickers based on zone and workload
        $availablePickers = Employee::where('department', 'warehouse')
            ->where('is_active', true)
            ->whereDoesntHave('currentZonePickAssignments')
            ->limit($zonePick->max_pickers)
            ->get();

        foreach ($availablePickers as $picker) {
            ZonePickAssignment::create([
                'zone_pick_id' => $zonePick->id,
                'picker_id' => $picker->id,
                'assignment_type' => 'primary',
                'status' => 'assigned',
                'assigned_by' => auth()->id(),
                'assigned_at' => now()
            ]);
        }

        $zonePick->update([
            'status' => 'assigned',
            'assigned_at' => now(),
            'assigned_pickers' => $availablePickers->count()
        ]);
    }

    private function getOptimizedPickPath($zonePick): array
    {
        return $zonePick->items()
            ->with(['product', 'location'])
            ->orderBy('sequence_number')
            ->get()
            ->map(function ($item) {
                return [
                    'sequence' => $item->sequence_number,
                    'product' => $item->product,
                    'location' => $item->location,
                    'required_quantity' => $item->required_quantity,
                    'picked_quantity' => $item->picked_quantity,
                    'status' => $item->status,
                    'assigned_picker' => $item->assignedPicker
                ];
            })
            ->toArray();
    }

    private function calculateProgress($zonePick): array
    {
        $totalItems = $zonePick->items()->count();
        $completedItems = $zonePick->items()->where('status', 'completed')->count();
        $inProgressItems = $zonePick->items()->where('status', 'in_progress')->count();
        
        return [
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'in_progress_items' => $inProgressItems,
            'pending_items' => $totalItems - $completedItems - $inProgressItems,
            'completion_percentage' => $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0,
            'picker_progress' => $this->getPickerProgress($zonePick)
        ];
    }

    private function getPickerProgress($zonePick): array
    {
        return $zonePick->assignments->map(function ($assignment) {
            $assignedItems = ZonePickItem::where('zone_pick_id', $assignment->zone_pick_id)
                ->where('assigned_picker_id', $assignment->picker_id)
                ->get();
            
            $completed = $assignedItems->where('status', 'completed')->count();
            $total = $assignedItems->count();
            
            return [
                'picker' => $assignment->picker,
                'assigned_items' => $total,
                'completed_items' => $completed,
                'progress_percentage' => $total > 0 ? ($completed / $total) * 100 : 0
            ];
        })->toArray();
    }

    private function regeneratePickPath($zonePick, $newStrategy): void
    {
        $orders = $zonePick->orders;
        $newOptimization = $this->generatePickPathOptimization($orders, $zonePick->zone_id, $newStrategy);
        
        $zonePick->update([
            'pick_path_optimization' => $newOptimization
        ]);

        // Update item sequences based on new path
        foreach ($newOptimization['path_sequence'] as $index => $pathItem) {
            ZonePickItem::where('zone_pick_id', $zonePick->id)
                ->where('sales_order_item_id', $pathItem['item_id'])
                ->update(['sequence_number' => $index + 1]);
        }
    }

    private function handleStatusChange($zonePick, $oldStatus, $newStatus): void
    {
        // Handle status-specific logic
        $statusTimestamps = [
            'assigned' => ['assigned_at' => now()],
            'in_progress' => ['started_at' => now()],
            'completed' => ['completed_at' => now()],
            'cancelled' => ['cancelled_at' => now()]
        ];

        if (isset($statusTimestamps[$newStatus])) {
            $zonePick->update($statusTimestamps[$newStatus]);
        }

        // Handle order status updates for cancellation
        if ($newStatus === 'cancelled') {
            foreach ($zonePick->orders as $order) {
                $originalStatus = $zonePick->orders()->where('sales_order_id', $order->id)->first()->pivot->original_status;
                $order->update(['status' => $originalStatus]);
            }
        }

        // Fire status change event
        $this->fireTransactionalEvent('outbound.zone_pick.status_changed', [
            'zone_pick_id' => $zonePick->id,
            'zone_pick_number' => $zonePick->zone_pick_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);
    }

    private function createPickTasks($zonePick): void
    {
        // Create individual pick tasks for mobile/handheld devices
        foreach ($zonePick->items as $item) {
            if ($item->assigned_picker_id) {
                // Create pick task for assigned picker
                // This would integrate with your mobile task system
            }
        }
    }

    private function processPickExceptions($zonePick, $exceptions): void
    {
        foreach ($exceptions as $exception) {
            $item = ZonePickItem::find($exception['item_id']);
            if ($item) {
                $item->update([
                    'picked_quantity' => $exception['quantity_picked'],
                    'status' => $exception['quantity_picked'] < $item->required_quantity ? 'short_picked' : 'completed',
                    'exception_type' => $exception['exception_type'],
                    'exception_notes' => $exception['notes'] ?? null
                ]);
            }
        }
    }

    private function calculateCompletionMetrics($zonePick): array
    {
        $items = $zonePick->items;
        $totalRequired = $items->sum('required_quantity');
        $totalPicked = $items->sum('picked_quantity');
        
        return [
            'total_items' => $items->count(),
            'completed_items' => $items->where('status', 'completed')->count(),
            'short_picked_items' => $items->where('status', 'short_picked')->count(),
            'total_required_quantity' => $totalRequired,
            'total_picked_quantity' => $totalPicked,
            'pick_accuracy' => $totalRequired > 0 ? ($totalPicked / $totalRequired) * 100 : 0,
            'picker_performance' => $this->calculatePickerPerformance($zonePick)
        ];
    }

    private function calculatePickerPerformance($zonePick): array
    {
        return $zonePick->assignments->map(function ($assignment) {
            $assignedItems = ZonePickItem::where('zone_pick_id', $assignment->zone_pick_id)
                ->where('assigned_picker_id', $assignment->picker_id)
                ->get();
            
            $totalRequired = $assignedItems->sum('required_quantity');
            $totalPicked = $assignedItems->sum('picked_quantity');
            
            return [
                'picker_id' => $assignment->picker_id,
                'picker_name' => $assignment->picker->name,
                'items_assigned' => $assignedItems->count(),
                'items_completed' => $assignedItems->where('status', 'completed')->count(),
                'pick_accuracy' => $totalRequired > 0 ? ($totalPicked / $totalRequired) * 100 : 0,
                'pick_rate' => $this->calculatePickRate($assignment)
            ];
        })->toArray();
    }

    private function calculatePickRate($assignment): float
    {
        if (!$assignment->started_at || !$assignment->completed_at) {
            return 0;
        }

        $timeWorked = $assignment->completed_at->diffInMinutes($assignment->started_at);
        $itemsPicked = ZonePickItem::where('zone_pick_id', $assignment->zone_pick_id)
            ->where('assigned_picker_id', $assignment->picker_id)
            ->where('status', 'completed')
            ->count();

        return $timeWorked > 0 ? $itemsPicked / $timeWorked : 0;
    }

    private function updateOrderStatuses($zonePick): void
    {
        foreach ($zonePick->orders as $order) {
            // Check if all items in this zone are picked
            $allItemsPicked = $this->areAllOrderItemsPickedInZone($order, $zonePick->zone_id);
            
            if ($allItemsPicked) {
                // Check if order has items in other zones
                $hasItemsInOtherZones = $this->orderHasItemsInOtherZones($order, $zonePick->zone_id);
                
                if ($hasItemsInOtherZones) {
                    $order->update(['status' => 'partially_picked']);
                } else {
                    $order->update(['status' => 'picked']);
                }
            }
        }
    }

    private function areAllOrderItemsPickedInZone($order, $zoneId): bool
    {
        $itemsInZone = $this->getOrderItemsInZone($order, $zoneId);
        
        foreach ($itemsInZone as $item) {
            $zonePickItem = ZonePickItem::where('sales_order_item_id', $item->id)
                ->where('status', 'completed')
                ->first();
            
            if (!$zonePickItem) {
                return false;
            }
        }
        
        return true;
    }

    private function orderHasItemsInOtherZones($order, $currentZoneId): bool
    {
        return $order->items()->whereHas('location', function ($query) use ($currentZoneId) {
            $query->where('zone_id', '!=', $currentZoneId);
        })->exists();
    }

    /**
     * Analytics helper methods
     */
    private function getZonePicksByZone($query): array
    {
        return $query->with('zone')
            ->selectRaw('zone_id, count(*) as pick_count, avg(actual_pick_time) as avg_time')
            ->groupBy('zone_id')
            ->get()
            ->map(function ($item) {
                return [
                    'zone' => $item->zone,
                    'pick_count' => $item->pick_count,
                    'avg_time' => $item->avg_time
                ];
            })
            ->toArray();
    }

    private function getPickerPerformance($query): array
    {
        return ZonePickAssignment::whereIn('zone_pick_id', $query->pluck('id'))
            ->with('picker')
            ->whereNotNull('completed_at')
            ->selectRaw('picker_id, count(*) as assignments, avg(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_time')
            ->groupBy('picker_id')
            ->get()
            ->map(function ($item) {
                return [
                    'picker' => $item->picker,
                    'assignments' => $item->assignments,
                    'avg_time' => $item->avg_time
                ];
            })
            ->toArray();
    }

    private function getEfficiencyMetrics($query): array
    {
        $completed = $query->where('status', 'completed');
        
        return [
            'completion_rate' => $query->count() > 0 ? ($completed->count() / $query->count()) * 100 : 0,
            'average_time_vs_estimate' => $completed->selectRaw('AVG(actual_pick_time - estimated_pick_time) as variance')->value('variance') ?? 0,
            'pick_accuracy' => $this->calculateOverallPickAccuracy($completed),
            'picker_utilization' => $this->calculatePickerUtilization($completed)
        ];
    }

    private function calculateOverallPickAccuracy($query): float
    {
        $zonePickIds = $query->pluck('id');
        $items = ZonePickItem::whereIn('zone_pick_id', $zonePickIds);
        
        $totalRequired = $items->sum('required_quantity');
        $totalPicked = $items->sum('picked_quantity');
        
        return $totalRequired > 0 ? ($totalPicked / $totalRequired) * 100 : 0;
    }

    private function calculatePickerUtilization($query): float
    {
        // Calculate picker utilization based on assignments and time worked
        return 85.0; // Placeholder
    }

    private function getZoneUtilization($query): array
    {
        return $query->with('zone')
            ->selectRaw('zone_id, count(*) as usage_count, avg(total_items) as avg_items')
            ->groupBy('zone_id')
            ->get()
            ->map(function ($item) {
                return [
                    'zone' => $item->zone,
                    'usage_count' => $item->usage_count,
                    'avg_items' => $item->avg_items,
                    'utilization_score' => min(100, $item->usage_count * 10) // Simple utilization score
                ];
            })
            ->toArray();
    }

    private function getPickTrends($dateFrom, $dateTo, $warehouseId, $zoneId): array
    {
        $query = ZonePick::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, count(*) as pick_count, avg(actual_pick_time) as avg_time, sum(total_items) as total_items')
            ->groupBy('date')
            ->orderBy('date');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($zoneId) {
            $query->where('zone_id', $zoneId);
        }

        return $query->get()->toArray();
    }
}