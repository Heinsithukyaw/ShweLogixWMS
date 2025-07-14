<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\BatchPick;
use App\Models\Outbound\BatchPickOrder;
use App\Models\Outbound\BatchPickItem;
use App\Models\SalesOrder;
use App\Models\PickTask;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class BatchPickController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of batch picks
     */
    public function index(Request $request): JsonResponse
    {
        $query = BatchPick::with(['warehouse', 'assignedPicker', 'orders.salesOrder.customer']);

        // Apply filters
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_picker_id')) {
            $query->where('assigned_picker_id', $request->assigned_picker_id);
        }

        if ($request->has('pick_type')) {
            $query->where('pick_type', $request->pick_type);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $batchPicks = $query->orderBy('priority_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $batchPicks,
            'message' => 'Batch picks retrieved successfully'
        ]);
    }

    /**
     * Store a newly created batch pick
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'pick_type' => 'required|in:single_order,multi_order,zone_based,product_based',
            'batch_strategy' => 'required|in:fifo,priority,zone_optimization,distance_optimization',
            'max_orders' => 'required|integer|min:1|max:50',
            'max_items' => 'required|integer|min:1|max:500',
            'max_weight' => 'nullable|numeric|min:0',
            'max_volume' => 'nullable|numeric|min:0',
            'assigned_picker_id' => 'nullable|exists:employees,id',
            'pick_zones' => 'nullable|array',
            'pick_zones.*' => 'exists:zones,id',
            'priority_threshold' => 'nullable|integer|min:0|max:100',
            'auto_assign' => 'boolean',
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

            // Generate batch number
            $batchNumber = $this->generateBatchNumber();

            // Get eligible orders for batching
            $eligibleOrders = $this->getEligibleOrders($request->all());

            if ($eligibleOrders->isEmpty()) {
                throw new \Exception('No eligible orders found for batch picking');
            }

            // Create batch pick
            $batchPick = BatchPick::create([
                'batch_number' => $batchNumber,
                'warehouse_id' => $request->warehouse_id,
                'pick_type' => $request->pick_type,
                'batch_strategy' => $request->batch_strategy,
                'status' => 'created',
                'max_orders' => $request->max_orders,
                'max_items' => $request->max_items,
                'max_weight' => $request->max_weight,
                'max_volume' => $request->max_volume,
                'assigned_picker_id' => $request->assigned_picker_id,
                'pick_zones' => $request->pick_zones ?? [],
                'priority_threshold' => $request->priority_threshold ?? 0,
                'auto_assign' => $request->auto_assign ?? false,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
                'priority_score' => $this->calculateBatchPriority($eligibleOrders),
                'estimated_pick_time' => $this->estimatePickTime($eligibleOrders),
                'optimization_data' => $this->generateOptimizationData($eligibleOrders, $request->all())
            ]);

            // Add orders to batch
            $this->addOrdersToBatch($batchPick, $eligibleOrders, $request->all());

            // Generate pick list items
            $this->generatePickListItems($batchPick);

            // Auto-assign if requested
            if ($request->auto_assign && !$request->assigned_picker_id) {
                $this->autoAssignPicker($batchPick);
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.batch_pick.created', [
                'batch_pick_id' => $batchPick->id,
                'batch_number' => $batchNumber,
                'warehouse_id' => $request->warehouse_id,
                'pick_type' => $request->pick_type,
                'order_count' => $eligibleOrders->count(),
                'total_items' => $eligibleOrders->sum('total_items'),
                'assigned_picker_id' => $batchPick->assigned_picker_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $batchPick->load(['warehouse', 'assignedPicker', 'orders.salesOrder.customer']),
                'message' => 'Batch pick created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create batch pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified batch pick
     */
    public function show($id): JsonResponse
    {
        $batchPick = BatchPick::with([
            'warehouse',
            'assignedPicker',
            'orders.salesOrder.customer',
            'items.product',
            'items.location',
            'createdBy'
        ])->find($id);

        if (!$batchPick) {
            return response()->json([
                'success' => false,
                'message' => 'Batch pick not found'
            ], 404);
        }

        // Get pick path optimization
        $pickPath = $this->getOptimizedPickPath($batchPick);

        return response()->json([
            'success' => true,
            'data' => array_merge($batchPick->toArray(), [
                'pick_path' => $pickPath,
                'progress' => $this->calculateProgress($batchPick)
            ]),
            'message' => 'Batch pick retrieved successfully'
        ]);
    }

    /**
     * Update the specified batch pick
     */
    public function update(Request $request, $id): JsonResponse
    {
        $batchPick = BatchPick::find($id);

        if (!$batchPick) {
            return response()->json([
                'success' => false,
                'message' => 'Batch pick not found'
            ], 404);
        }

        if (!in_array($batchPick->status, ['created', 'assigned', 'in_progress'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update batch pick in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'assigned_picker_id' => 'nullable|exists:employees,id',
            'priority_threshold' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
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

            $oldStatus = $batchPick->status;
            $oldPicker = $batchPick->assigned_picker_id;

            $batchPick->update($request->only([
                'assigned_picker_id',
                'priority_threshold',
                'notes',
                'status'
            ]));

            // Handle status changes
            if ($request->has('status') && $oldStatus !== $request->status) {
                $this->handleStatusChange($batchPick, $oldStatus, $request->status);
            }

            // Handle picker assignment changes
            if ($request->has('assigned_picker_id') && $oldPicker !== $request->assigned_picker_id) {
                $this->handlePickerAssignment($batchPick, $oldPicker, $request->assigned_picker_id);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $batchPick->load(['warehouse', 'assignedPicker', 'orders.salesOrder.customer']),
                'message' => 'Batch pick updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update batch pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start batch pick
     */
    public function start(Request $request, $id): JsonResponse
    {
        $batchPick = BatchPick::find($id);

        if (!$batchPick) {
            return response()->json([
                'success' => false,
                'message' => 'Batch pick not found'
            ], 404);
        }

        if ($batchPick->status !== 'assigned') {
            return response()->json([
                'success' => false,
                'message' => 'Batch pick must be assigned before starting'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $batchPick->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'started_by' => auth()->id()
            ]);

            // Create individual pick tasks
            $this->createPickTasks($batchPick);

            // Fire event
            $this->fireTransactionalEvent('outbound.batch_pick.started', [
                'batch_pick_id' => $batchPick->id,
                'batch_number' => $batchPick->batch_number,
                'started_by' => auth()->id(),
                'assigned_picker_id' => $batchPick->assigned_picker_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $batchPick->load(['warehouse', 'assignedPicker', 'items.product']),
                'message' => 'Batch pick started successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to start batch pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete batch pick
     */
    public function complete(Request $request, $id): JsonResponse
    {
        $batchPick = BatchPick::find($id);

        if (!$batchPick) {
            return response()->json([
                'success' => false,
                'message' => 'Batch pick not found'
            ], 404);
        }

        if ($batchPick->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Batch pick is not in progress'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'completion_notes' => 'nullable|string',
            'exceptions' => 'nullable|array',
            'exceptions.*.item_id' => 'required|exists:batch_pick_items,id',
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
                $this->processPickExceptions($batchPick, $request->exceptions);
            }

            // Calculate completion metrics
            $completionMetrics = $this->calculateCompletionMetrics($batchPick);

            $batchPick->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'completion_notes' => $request->completion_notes,
                'actual_pick_time' => now()->diffInMinutes($batchPick->started_at),
                'completion_metrics' => $completionMetrics
            ]);

            // Update order statuses
            $this->updateOrderStatuses($batchPick);

            // Fire event
            $this->fireTransactionalEvent('outbound.batch_pick.completed', [
                'batch_pick_id' => $batchPick->id,
                'batch_number' => $batchPick->batch_number,
                'completed_by' => auth()->id(),
                'actual_pick_time' => $batchPick->actual_pick_time,
                'completion_metrics' => $completionMetrics
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $batchPick->load(['warehouse', 'assignedPicker', 'orders.salesOrder.customer']),
                'message' => 'Batch pick completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete batch pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batch pick analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = BatchPick::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $analytics = [
            'total_batches' => $query->count(),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_pick_type' => $query->groupBy('pick_type')->selectRaw('pick_type, count(*) as count')->pluck('count', 'pick_type'),
            'average_pick_time' => $query->whereNotNull('actual_pick_time')->avg('actual_pick_time'),
            'average_orders_per_batch' => $this->getAverageOrdersPerBatch($query),
            'average_items_per_batch' => $this->getAverageItemsPerBatch($query),
            'picker_performance' => $this->getPickerPerformance($query),
            'efficiency_metrics' => $this->getEfficiencyMetrics($query),
            'batch_trends' => $this->getBatchTrends($dateFrom, $dateTo, $warehouseId)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Batch pick analytics retrieved successfully'
        ]);
    }

    /**
     * Generate batch number
     */
    private function generateBatchNumber(): string
    {
        $year = date('Y');
        $sequence = BatchPick::whereYear('created_at', $year)->count() + 1;
        
        return 'BP-' . $year . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get eligible orders for batching
     */
    private function getEligibleOrders(array $criteria): \Illuminate\Database\Eloquent\Collection
    {
        $query = SalesOrder::with(['items.product', 'customer'])
            ->where('warehouse_id', $criteria['warehouse_id'])
            ->where('status', 'allocated')
            ->whereDoesntHave('batchPickOrders');

        // Apply priority threshold
        if (isset($criteria['priority_threshold'])) {
            $query->where('priority_score', '>=', $criteria['priority_threshold']);
        }

        // Apply zone filtering
        if (isset($criteria['pick_zones']) && !empty($criteria['pick_zones'])) {
            $query->whereHas('items.location', function ($q) use ($criteria) {
                $q->whereIn('zone_id', $criteria['pick_zones']);
            });
        }

        // Apply batch strategy ordering
        switch ($criteria['batch_strategy']) {
            case 'fifo':
                $query->orderBy('created_at', 'asc');
                break;
            case 'priority':
                $query->orderBy('priority_score', 'desc');
                break;
            case 'zone_optimization':
                $query->orderBy('primary_pick_zone', 'asc');
                break;
            case 'distance_optimization':
                $query->orderBy('estimated_pick_distance', 'asc');
                break;
        }

        return $query->limit($criteria['max_orders'])->get();
    }

    /**
     * Add orders to batch
     */
    private function addOrdersToBatch($batchPick, $orders, $criteria): void
    {
        $totalItems = 0;
        $totalWeight = 0;
        $totalVolume = 0;

        foreach ($orders as $order) {
            // Check if adding this order would exceed limits
            $orderItems = $order->items->count();
            $orderWeight = $order->total_weight ?? 0;
            $orderVolume = $order->total_volume ?? 0;

            if (($totalItems + $orderItems) > $criteria['max_items']) {
                break;
            }

            if (isset($criteria['max_weight']) && ($totalWeight + $orderWeight) > $criteria['max_weight']) {
                break;
            }

            if (isset($criteria['max_volume']) && ($totalVolume + $orderVolume) > $criteria['max_volume']) {
                break;
            }

            // Add order to batch
            BatchPickOrder::create([
                'batch_pick_id' => $batchPick->id,
                'sales_order_id' => $order->id,
                'sequence_number' => BatchPickOrder::where('batch_pick_id', $batchPick->id)->count() + 1,
                'priority_score' => $order->priority_score ?? 0,
                'estimated_pick_time' => $this->estimateOrderPickTime($order)
            ]);

            $totalItems += $orderItems;
            $totalWeight += $orderWeight;
            $totalVolume += $orderVolume;
        }

        // Update batch totals
        $batchPick->update([
            'total_orders' => BatchPickOrder::where('batch_pick_id', $batchPick->id)->count(),
            'total_items' => $totalItems,
            'total_weight' => $totalWeight,
            'total_volume' => $totalVolume
        ]);
    }

    /**
     * Generate pick list items
     */
    private function generatePickListItems($batchPick): void
    {
        $orders = $batchPick->orders()->with('salesOrder.items.product')->get();
        $consolidatedItems = [];

        // Consolidate items by product and location
        foreach ($orders as $batchOrder) {
            foreach ($batchOrder->salesOrder->items as $orderItem) {
                $key = $orderItem->product_id . '-' . $orderItem->location_id;
                
                if (!isset($consolidatedItems[$key])) {
                    $consolidatedItems[$key] = [
                        'product_id' => $orderItem->product_id,
                        'location_id' => $orderItem->location_id,
                        'total_quantity' => 0,
                        'orders' => []
                    ];
                }
                
                $consolidatedItems[$key]['total_quantity'] += $orderItem->quantity;
                $consolidatedItems[$key]['orders'][] = [
                    'sales_order_id' => $batchOrder->sales_order_id,
                    'quantity' => $orderItem->quantity
                ];
            }
        }

        // Create batch pick items
        $sequence = 1;
        foreach ($consolidatedItems as $item) {
            BatchPickItem::create([
                'batch_pick_id' => $batchPick->id,
                'product_id' => $item['product_id'],
                'location_id' => $item['location_id'],
                'sequence_number' => $sequence++,
                'required_quantity' => $item['total_quantity'],
                'picked_quantity' => 0,
                'status' => 'pending',
                'order_details' => $item['orders']
            ]);
        }
    }

    /**
     * Helper methods for calculations and optimizations
     */
    private function calculateBatchPriority($orders): int
    {
        return $orders->avg('priority_score') ?? 50;
    }

    private function estimatePickTime($orders): int
    {
        // Estimate based on number of items and locations
        $totalItems = $orders->sum('total_items');
        $estimatedMinutes = ($totalItems * 0.5) + 10; // Base time + item time
        
        return (int) $estimatedMinutes;
    }

    private function estimateOrderPickTime($order): int
    {
        $itemCount = $order->items->count();
        return ($itemCount * 0.5) + 2; // Estimated minutes per order
    }

    private function generateOptimizationData($orders, $criteria): array
    {
        return [
            'batch_strategy' => $criteria['batch_strategy'],
            'total_estimated_distance' => $this->calculateTotalDistance($orders),
            'zone_distribution' => $this->getZoneDistribution($orders),
            'optimization_score' => $this->calculateOptimizationScore($orders, $criteria)
        ];
    }

    private function calculateTotalDistance($orders): float
    {
        // Simplified distance calculation
        return $orders->sum('estimated_pick_distance') ?? 0;
    }

    private function getZoneDistribution($orders): array
    {
        // Get distribution of items across zones
        return [];
    }

    private function calculateOptimizationScore($orders, $criteria): int
    {
        // Calculate optimization score based on various factors
        return 75; // Placeholder
    }

    private function getOptimizedPickPath($batchPick): array
    {
        // Generate optimized pick path
        return $batchPick->items()
            ->with(['product', 'location'])
            ->orderBy('sequence_number')
            ->get()
            ->toArray();
    }

    private function calculateProgress($batchPick): array
    {
        $totalItems = $batchPick->items()->count();
        $completedItems = $batchPick->items()->where('status', 'completed')->count();
        
        return [
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'progress_percentage' => $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0
        ];
    }

    private function handleStatusChange($batchPick, $oldStatus, $newStatus): void
    {
        // Handle status-specific logic
        $statusTimestamps = [
            'assigned' => ['assigned_at' => now()],
            'in_progress' => ['started_at' => now()],
            'completed' => ['completed_at' => now()],
            'cancelled' => ['cancelled_at' => now()]
        ];

        if (isset($statusTimestamps[$newStatus])) {
            $batchPick->update($statusTimestamps[$newStatus]);
        }

        // Fire status change event
        $this->fireTransactionalEvent('outbound.batch_pick.status_changed', [
            'batch_pick_id' => $batchPick->id,
            'batch_number' => $batchPick->batch_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);
    }

    private function handlePickerAssignment($batchPick, $oldPicker, $newPicker): void
    {
        if ($newPicker && $batchPick->status === 'created') {
            $batchPick->update(['status' => 'assigned']);
        }

        // Fire picker assignment event
        $this->fireTransactionalEvent('outbound.batch_pick.picker_assigned', [
            'batch_pick_id' => $batchPick->id,
            'batch_number' => $batchPick->batch_number,
            'old_picker_id' => $oldPicker,
            'new_picker_id' => $newPicker,
            'assigned_by' => auth()->id()
        ]);
    }

    private function autoAssignPicker($batchPick): void
    {
        // Auto-assign logic based on availability and workload
        // This would integrate with your labor management system
    }

    private function createPickTasks($batchPick): void
    {
        // Create individual pick tasks for mobile/handheld devices
        foreach ($batchPick->items as $item) {
            PickTask::create([
                'batch_pick_id' => $batchPick->id,
                'product_id' => $item->product_id,
                'location_id' => $item->location_id,
                'required_quantity' => $item->required_quantity,
                'assigned_to' => $batchPick->assigned_picker_id,
                'status' => 'assigned',
                'task_type' => 'batch_pick'
            ]);
        }
    }

    private function processPickExceptions($batchPick, $exceptions): void
    {
        foreach ($exceptions as $exception) {
            $item = BatchPickItem::find($exception['item_id']);
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

    private function calculateCompletionMetrics($batchPick): array
    {
        $items = $batchPick->items;
        $totalRequired = $items->sum('required_quantity');
        $totalPicked = $items->sum('picked_quantity');
        
        return [
            'total_items' => $items->count(),
            'completed_items' => $items->where('status', 'completed')->count(),
            'short_picked_items' => $items->where('status', 'short_picked')->count(),
            'total_required_quantity' => $totalRequired,
            'total_picked_quantity' => $totalPicked,
            'pick_accuracy' => $totalRequired > 0 ? ($totalPicked / $totalRequired) * 100 : 0
        ];
    }

    private function updateOrderStatuses($batchPick): void
    {
        foreach ($batchPick->orders as $batchOrder) {
            $salesOrder = $batchOrder->salesOrder;
            $salesOrder->update(['status' => 'picked']);
        }
    }

    // Analytics helper methods
    private function getAverageOrdersPerBatch($query): float
    {
        return $query->avg('total_orders') ?? 0;
    }

    private function getAverageItemsPerBatch($query): float
    {
        return $query->avg('total_items') ?? 0;
    }

    private function getPickerPerformance($query): array
    {
        return $query->with('assignedPicker')
            ->whereNotNull('assigned_picker_id')
            ->whereNotNull('actual_pick_time')
            ->selectRaw('assigned_picker_id, AVG(actual_pick_time) as avg_time, COUNT(*) as batch_count')
            ->groupBy('assigned_picker_id')
            ->get()
            ->toArray();
    }

    private function getEfficiencyMetrics($query): array
    {
        $completed = $query->where('status', 'completed');
        
        return [
            'average_time_vs_estimate' => $completed->selectRaw('AVG(actual_pick_time - estimated_pick_time) as variance')->value('variance') ?? 0,
            'on_time_completion_rate' => $this->calculateOnTimeRate($completed),
            'average_pick_rate' => $this->calculateAveragePickRate($completed)
        ];
    }

    private function calculateOnTimeRate($query): float
    {
        $total = $query->count();
        $onTime = $query->whereRaw('actual_pick_time <= estimated_pick_time')->count();
        
        return $total > 0 ? ($onTime / $total) * 100 : 0;
    }

    private function calculateAveragePickRate($query): float
    {
        return $query->selectRaw('AVG(total_items / actual_pick_time) as rate')->value('rate') ?? 0;
    }

    private function getBatchTrends($dateFrom, $dateTo, $warehouseId): array
    {
        $query = BatchPick::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as batch_count, AVG(actual_pick_time) as avg_time')
            ->groupBy('date')
            ->orderBy('date');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->toArray();
    }
}