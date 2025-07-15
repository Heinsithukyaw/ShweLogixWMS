<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\ClusterPick;
use App\Models\Outbound\ClusterPickOrder;
use App\Models\Outbound\ClusterPickItem;
use App\Models\SalesOrder;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class ClusterPickController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of cluster picks
     */
    public function index(Request $request): JsonResponse
    {
        $query = ClusterPick::with(['warehouse', 'assignedPicker', 'orders.salesOrder.customer']);

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

        if ($request->has('cluster_strategy')) {
            $query->where('cluster_strategy', $request->cluster_strategy);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $clusterPicks = $query->orderBy('priority_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $clusterPicks,
            'message' => 'Cluster picks retrieved successfully'
        ]);
    }

    /**
     * Store a newly created cluster pick
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'cluster_strategy' => 'required|in:location_based,product_based,customer_based,mixed_optimization',
            'orders' => 'required|array|min:2|max:20',
            'orders.*' => 'exists:sales_orders,id',
            'max_cluster_size' => 'required|integer|min:2|max:20',
            'max_weight' => 'nullable|numeric|min:0',
            'max_volume' => 'nullable|numeric|min:0',
            'assigned_picker_id' => 'nullable|exists:employees,id',
            'cluster_zones' => 'nullable|array',
            'cluster_zones.*' => 'exists:zones,id',
            'pick_deadline' => 'nullable|date|after:now',
            'special_instructions' => 'nullable|string',
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

            // Validate orders are eligible for cluster picking
            $orders = SalesOrder::whereIn('id', $request->orders)
                ->where('warehouse_id', $request->warehouse_id)
                ->whereIn('status', ['allocated', 'released'])
                ->get();

            if ($orders->count() !== count($request->orders)) {
                throw new \Exception('Some orders are not eligible for cluster picking');
            }

            // Validate cluster constraints
            $this->validateClusterConstraints($orders, $request->all());

            // Generate cluster pick number
            $clusterNumber = $this->generateClusterNumber();

            // Calculate cluster metrics
            $metrics = $this->calculateClusterMetrics($orders);

            // Create cluster pick
            $clusterPick = ClusterPick::create([
                'cluster_number' => $clusterNumber,
                'warehouse_id' => $request->warehouse_id,
                'cluster_strategy' => $request->cluster_strategy,
                'status' => 'created',
                'max_cluster_size' => $request->max_cluster_size,
                'max_weight' => $request->max_weight,
                'max_volume' => $request->max_volume,
                'assigned_picker_id' => $request->assigned_picker_id,
                'cluster_zones' => $request->cluster_zones ?? [],
                'pick_deadline' => $request->pick_deadline,
                'special_instructions' => $request->special_instructions,
                'auto_optimize' => $request->auto_optimize ?? true,
                'total_orders' => $orders->count(),
                'total_items' => $metrics['total_items'],
                'total_quantity' => $metrics['total_quantity'],
                'total_weight' => $metrics['total_weight'],
                'total_volume' => $metrics['total_volume'],
                'priority_score' => $this->calculatePriorityScore($orders),
                'estimated_pick_time' => $this->estimatePickTime($metrics),
                'cluster_efficiency_score' => $this->calculateClusterEfficiency($orders, $request->cluster_strategy),
                'created_by' => auth()->id(),
                'optimization_data' => $this->generateOptimizationData($orders, $request->all())
            ]);

            // Add orders to cluster
            $this->addOrdersToCluster($clusterPick, $orders);

            // Generate cluster pick items
            $this->generateClusterPickItems($clusterPick);

            // Apply optimization if requested
            if ($request->auto_optimize) {
                $this->optimizeClusterPick($clusterPick);
            }

            // Auto-assign picker if specified
            if ($request->assigned_picker_id) {
                $clusterPick->update(['status' => 'assigned']);
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.cluster_pick.created', [
                'cluster_pick_id' => $clusterPick->id,
                'cluster_number' => $clusterNumber,
                'warehouse_id' => $request->warehouse_id,
                'cluster_strategy' => $request->cluster_strategy,
                'order_count' => $orders->count(),
                'total_items' => $metrics['total_items'],
                'assigned_picker_id' => $request->assigned_picker_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $clusterPick->load(['warehouse', 'assignedPicker', 'orders.salesOrder.customer']),
                'message' => 'Cluster pick created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create cluster pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified cluster pick
     */
    public function show($id): JsonResponse
    {
        $clusterPick = ClusterPick::with([
            'warehouse',
            'assignedPicker',
            'orders.salesOrder.customer',
            'items.product',
            'items.location',
            'createdBy'
        ])->find($id);

        if (!$clusterPick) {
            return response()->json([
                'success' => false,
                'message' => 'Cluster pick not found'
            ], 404);
        }

        // Get optimized pick sequence
        $pickSequence = $this->getOptimizedPickSequence($clusterPick);

        // Get cluster analysis
        $analysis = $this->getClusterAnalysis($clusterPick);

        return response()->json([
            'success' => true,
            'data' => array_merge($clusterPick->toArray(), [
                'pick_sequence' => $pickSequence,
                'analysis' => $analysis
            ]),
            'message' => 'Cluster pick retrieved successfully'
        ]);
    }

    /**
     * Update the specified cluster pick
     */
    public function update(Request $request, $id): JsonResponse
    {
        $clusterPick = ClusterPick::find($id);

        if (!$clusterPick) {
            return response()->json([
                'success' => false,
                'message' => 'Cluster pick not found'
            ], 404);
        }

        if (!in_array($clusterPick->status, ['created', 'assigned'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update cluster pick in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'assigned_picker_id' => 'nullable|exists:employees,id',
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

            $oldStatus = $clusterPick->status;
            $oldPicker = $clusterPick->assigned_picker_id;

            $clusterPick->update($request->only([
                'assigned_picker_id',
                'pick_deadline',
                'special_instructions',
                'status'
            ]));

            // Handle picker assignment changes
            if ($request->has('assigned_picker_id') && $oldPicker !== $request->assigned_picker_id) {
                $this->handlePickerAssignment($clusterPick, $oldPicker, $request->assigned_picker_id);
            }

            // Handle status changes
            if ($request->has('status') && $oldStatus !== $request->status) {
                $this->handleStatusChange($clusterPick, $oldStatus, $request->status);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $clusterPick->load(['warehouse', 'assignedPicker', 'orders.salesOrder.customer']),
                'message' => 'Cluster pick updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cluster pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start cluster pick
     */
    public function start(Request $request, $id): JsonResponse
    {
        $clusterPick = ClusterPick::find($id);

        if (!$clusterPick) {
            return response()->json([
                'success' => false,
                'message' => 'Cluster pick not found'
            ], 404);
        }

        if ($clusterPick->status !== 'assigned') {
            return response()->json([
                'success' => false,
                'message' => 'Cluster pick must be assigned before starting'
            ], 400);
        }

        if (!$clusterPick->assigned_picker_id) {
            return response()->json([
                'success' => false,
                'message' => 'No picker assigned to this cluster pick'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $clusterPick->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'started_by' => auth()->id()
            ]);

            // Update order statuses
            foreach ($clusterPick->orders as $clusterOrder) {
                $clusterOrder->salesOrder->update(['status' => 'cluster_picking']);
            }

            // Create mobile pick tasks
            $this->createMobilePickTasks($clusterPick);

            // Fire event
            $this->fireTransactionalEvent('outbound.cluster_pick.started', [
                'cluster_pick_id' => $clusterPick->id,
                'cluster_number' => $clusterPick->cluster_number,
                'assigned_picker_id' => $clusterPick->assigned_picker_id,
                'started_by' => auth()->id(),
                'order_count' => $clusterPick->total_orders
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $clusterPick->load(['warehouse', 'assignedPicker', 'orders.salesOrder.customer']),
                'message' => 'Cluster pick started successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to start cluster pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete cluster pick
     */
    public function complete(Request $request, $id): JsonResponse
    {
        $clusterPick = ClusterPick::find($id);

        if (!$clusterPick) {
            return response()->json([
                'success' => false,
                'message' => 'Cluster pick not found'
            ], 404);
        }

        if ($clusterPick->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Cluster pick is not in progress'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'completion_notes' => 'nullable|string',
            'exceptions' => 'nullable|array',
            'exceptions.*.item_id' => 'required|exists:cluster_pick_items,id',
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
                $this->processPickExceptions($clusterPick, $request->exceptions);
            }

            // Calculate completion metrics
            $completionMetrics = $this->calculateCompletionMetrics($clusterPick);

            $clusterPick->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'completion_notes' => $request->completion_notes,
                'actual_pick_time' => $clusterPick->started_at ? now()->diffInMinutes($clusterPick->started_at) : null,
                'completion_metrics' => $completionMetrics
            ]);

            // Update order statuses
            $this->updateOrderStatuses($clusterPick);

            // Generate pick summary for each order
            $this->generateOrderPickSummaries($clusterPick);

            // Fire event
            $this->fireTransactionalEvent('outbound.cluster_pick.completed', [
                'cluster_pick_id' => $clusterPick->id,
                'cluster_number' => $clusterPick->cluster_number,
                'completed_by' => auth()->id(),
                'actual_pick_time' => $clusterPick->actual_pick_time,
                'completion_metrics' => $completionMetrics
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $clusterPick->load(['warehouse', 'assignedPicker', 'orders.salesOrder.customer']),
                'message' => 'Cluster pick completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete cluster pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize cluster pick sequence
     */
    public function optimize(Request $request, $id): JsonResponse
    {
        $clusterPick = ClusterPick::find($id);

        if (!$clusterPick) {
            return response()->json([
                'success' => false,
                'message' => 'Cluster pick not found'
            ], 404);
        }

        if (!in_array($clusterPick->status, ['created', 'assigned'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot optimize cluster pick in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'optimization_type' => 'required|in:distance,time,priority,mixed',
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
            $optimizationResult = $this->performOptimization(
                $clusterPick,
                $request->optimization_type,
                $request->constraints ?? [],
                $request->force_reoptimize ?? false
            );

            // Update cluster pick with optimization results
            $clusterPick->update([
                'optimization_data' => $optimizationResult,
                'cluster_efficiency_score' => $optimizationResult['efficiency_score'],
                'estimated_pick_time' => $optimizationResult['estimated_time']
            ]);

            // Update item sequences based on optimization
            $this->updateItemSequences($clusterPick, $optimizationResult['pick_sequence']);

            // Fire event
            $this->fireTransactionalEvent('outbound.cluster_pick.optimized', [
                'cluster_pick_id' => $clusterPick->id,
                'cluster_number' => $clusterPick->cluster_number,
                'optimization_type' => $request->optimization_type,
                'efficiency_improvement' => $optimizationResult['efficiency_improvement'],
                'optimized_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'cluster_pick' => $clusterPick->load(['warehouse', 'assignedPicker']),
                    'optimization_result' => $optimizationResult
                ],
                'message' => 'Cluster pick optimized successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize cluster pick: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cluster pick analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = ClusterPick::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $analytics = [
            'total_cluster_picks' => $query->count(),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_strategy' => $query->groupBy('cluster_strategy')->selectRaw('cluster_strategy, count(*) as count')->pluck('count', 'cluster_strategy'),
            'average_cluster_size' => $query->avg('total_orders'),
            'average_pick_time' => $query->whereNotNull('actual_pick_time')->avg('actual_pick_time'),
            'average_efficiency_score' => $query->avg('cluster_efficiency_score'),
            'picker_performance' => $this->getPickerPerformance($query),
            'efficiency_metrics' => $this->getEfficiencyMetrics($query),
            'optimization_impact' => $this->getOptimizationImpact($query),
            'cluster_trends' => $this->getClusterTrends($dateFrom, $dateTo, $warehouseId)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Cluster pick analytics retrieved successfully'
        ]);
    }

    /**
     * Helper methods
     */
    private function generateClusterNumber(): string
    {
        $year = date('Y');
        $sequence = ClusterPick::whereYear('created_at', $year)->count() + 1;
        
        return 'CP-' . $year . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    private function validateClusterConstraints($orders, $criteria): void
    {
        $totalWeight = $orders->sum('total_weight');
        $totalVolume = $orders->sum('total_volume');

        if (isset($criteria['max_weight']) && $totalWeight > $criteria['max_weight']) {
            throw new \Exception('Total weight exceeds maximum cluster weight limit');
        }

        if (isset($criteria['max_volume']) && $totalVolume > $criteria['max_volume']) {
            throw new \Exception('Total volume exceeds maximum cluster volume limit');
        }

        if ($orders->count() > $criteria['max_cluster_size']) {
            throw new \Exception('Number of orders exceeds maximum cluster size');
        }
    }

    private function calculateClusterMetrics($orders): array
    {
        return [
            'total_items' => $orders->sum(function ($order) {
                return $order->items->count();
            }),
            'total_quantity' => $orders->sum(function ($order) {
                return $order->items->sum('quantity');
            }),
            'total_weight' => $orders->sum('total_weight'),
            'total_volume' => $orders->sum('total_volume')
        ];
    }

    private function calculatePriorityScore($orders): int
    {
        return $orders->avg('priority_score') ?? 50;
    }

    private function estimatePickTime($metrics): int
    {
        // Base time + item time + quantity time
        $baseTime = 15; // Base cluster setup time
        $itemTime = $metrics['total_items'] * 0.8; // 48 seconds per item
        $quantityTime = $metrics['total_quantity'] * 0.15; // 9 seconds per unit
        
        return (int) ($baseTime + $itemTime + $quantityTime);
    }

    private function calculateClusterEfficiency($orders, $strategy): int
    {
        $score = 50; // Base score

        // Order count efficiency
        $orderCount = $orders->count();
        $score += min(25, $orderCount * 3);

        // Strategy bonus
        $strategyBonus = [
            'location_based' => 15,
            'product_based' => 10,
            'customer_based' => 12,
            'mixed_optimization' => 20
        ];
        $score += $strategyBonus[$strategy] ?? 0;

        // Customer consolidation bonus
        $uniqueCustomers = $orders->pluck('customer_id')->unique()->count();
        if ($uniqueCustomers < $orderCount) {
            $score += (($orderCount - $uniqueCustomers) * 2);
        }

        return min(100, max(0, $score));
    }

    private function generateOptimizationData($orders, $criteria): array
    {
        return [
            'strategy' => $criteria['cluster_strategy'],
            'order_grouping' => $this->analyzeOrderGrouping($orders, $criteria['cluster_strategy']),
            'location_distribution' => $this->analyzeLocationDistribution($orders),
            'pick_density' => $this->calculatePickDensity($orders),
            'travel_distance_estimate' => $this->estimateTravelDistance($orders),
            'optimization_opportunities' => $this->identifyOptimizationOpportunities($orders)
        ];
    }

    private function analyzeOrderGrouping($orders, $strategy): array
    {
        switch ($strategy) {
            case 'customer_based':
                return $this->groupByCustomer($orders);
            case 'location_based':
                return $this->groupByLocation($orders);
            case 'product_based':
                return $this->groupByProduct($orders);
            default:
                return $this->groupMixed($orders);
        }
    }

    private function groupByCustomer($orders): array
    {
        return $orders->groupBy('customer_id')->map(function ($customerOrders) {
            return [
                'customer_id' => $customerOrders->first()->customer_id,
                'order_count' => $customerOrders->count(),
                'total_items' => $customerOrders->sum(function ($order) {
                    return $order->items->count();
                })
            ];
        })->values()->toArray();
    }

    private function groupByLocation($orders): array
    {
        // Group by primary pick locations
        $locationGroups = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $locationId = $item->location_id;
                if (!isset($locationGroups[$locationId])) {
                    $locationGroups[$locationId] = [
                        'location_id' => $locationId,
                        'item_count' => 0,
                        'order_count' => 0
                    ];
                }
                $locationGroups[$locationId]['item_count']++;
            }
        }
        return array_values($locationGroups);
    }

    private function groupByProduct($orders): array
    {
        $productGroups = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $productId = $item->product_id;
                if (!isset($productGroups[$productId])) {
                    $productGroups[$productId] = [
                        'product_id' => $productId,
                        'total_quantity' => 0,
                        'order_count' => 0
                    ];
                }
                $productGroups[$productId]['total_quantity'] += $item->quantity;
            }
        }
        return array_values($productGroups);
    }

    private function groupMixed($orders): array
    {
        return [
            'customer_groups' => $this->groupByCustomer($orders),
            'location_groups' => $this->groupByLocation($orders),
            'product_groups' => $this->groupByProduct($orders)
        ];
    }

    private function analyzeLocationDistribution($orders): array
    {
        $zones = [];
        $aisles = [];
        
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->location && $item->location->zone) {
                    $zoneId = $item->location->zone_id;
                    $zones[$zoneId] = ($zones[$zoneId] ?? 0) + 1;
                }
                
                if ($item->location && $item->location->aisle) {
                    $aisle = $item->location->aisle;
                    $aisles[$aisle] = ($aisles[$aisle] ?? 0) + 1;
                }
            }
        }

        return [
            'zone_distribution' => $zones,
            'aisle_distribution' => $aisles,
            'zone_count' => count($zones),
            'aisle_count' => count($aisles)
        ];
    }

    private function calculatePickDensity($orders): float
    {
        $totalItems = $orders->sum(function ($order) {
            return $order->items->count();
        });
        
        $uniqueLocations = collect();
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $uniqueLocations->push($item->location_id);
            }
        }
        
        $locationCount = $uniqueLocations->unique()->count();
        
        return $locationCount > 0 ? $totalItems / $locationCount : 0;
    }

    private function estimateTravelDistance($orders): float
    {
        // Simplified distance estimation
        $locations = collect();
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $locations->push($item->location_id);
            }
        }
        
        $uniqueLocations = $locations->unique()->count();
        return $uniqueLocations * 15; // Estimated 15 meters between locations
    }

    private function identifyOptimizationOpportunities($orders): array
    {
        $opportunities = [];
        
        // Check for customer consolidation opportunities
        $customerGroups = $orders->groupBy('customer_id');
        if ($customerGroups->count() < $orders->count()) {
            $opportunities[] = [
                'type' => 'customer_consolidation',
                'potential_savings' => ($orders->count() - $customerGroups->count()) * 2,
                'description' => 'Multiple orders from same customers can be consolidated'
            ];
        }
        
        // Check for location clustering opportunities
        $locationDistribution = $this->analyzeLocationDistribution($orders);
        if ($locationDistribution['zone_count'] <= 3) {
            $opportunities[] = [
                'type' => 'zone_clustering',
                'potential_savings' => 5,
                'description' => 'Items are concentrated in few zones, enabling efficient picking'
            ];
        }
        
        return $opportunities;
    }

    private function addOrdersToCluster($clusterPick, $orders): void
    {
        foreach ($orders as $order) {
            ClusterPickOrder::create([
                'cluster_pick_id' => $clusterPick->id,
                'sales_order_id' => $order->id,
                'cluster_position' => ClusterPickOrder::where('cluster_pick_id', $clusterPick->id)->count() + 1,
                'original_status' => $order->status,
                'priority_score' => $order->priority_score ?? 0,
                'estimated_pick_time' => $this->estimateOrderPickTime($order),
                'item_count' => $order->items->count()
            ]);

            // Update order status
            $order->update(['status' => 'cluster_assigned']);
        }
    }

    private function estimateOrderPickTime($order): int
    {
        return $order->items->count() * 0.8; // 48 seconds per item
    }

    private function generateClusterPickItems($clusterPick): void
    {
        $sequence = 1;
        
        foreach ($clusterPick->orders as $clusterOrder) {
            foreach ($clusterOrder->salesOrder->items as $orderItem) {
                ClusterPickItem::create([
                    'cluster_pick_id' => $clusterPick->id,
                    'cluster_pick_order_id' => $clusterOrder->id,
                    'sales_order_id' => $clusterOrder->sales_order_id,
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'location_id' => $orderItem->location_id,
                    'sequence_number' => $sequence++,
                    'required_quantity' => $orderItem->quantity,
                    'picked_quantity' => 0,
                    'status' => 'pending',
                    'cluster_position' => $clusterOrder->cluster_position
                ]);
            }
        }
    }

    private function optimizeClusterPick($clusterPick): void
    {
        // Apply automatic optimization based on cluster strategy
        $optimizationResult = $this->performOptimization(
            $clusterPick,
            'mixed',
            [],
            false
        );

        $clusterPick->update([
            'optimization_data' => $optimizationResult,
            'cluster_efficiency_score' => $optimizationResult['efficiency_score']
        ]);

        $this->updateItemSequences($clusterPick, $optimizationResult['pick_sequence']);
    }

    private function getOptimizedPickSequence($clusterPick): array
    {
        return $clusterPick->items()
            ->with(['product', 'location', 'clusterOrder.salesOrder.customer'])
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
                    'cluster_position' => $item->cluster_position,
                    'customer' => $item->clusterOrder->salesOrder->customer,
                    'order_number' => $item->clusterOrder->salesOrder->order_number
                ];
            })
            ->toArray();
    }

    private function getClusterAnalysis($clusterPick): array
    {
        return [
            'efficiency_metrics' => [
                'cluster_efficiency_score' => $clusterPick->cluster_efficiency_score,
                'pick_density' => $this->calculateCurrentPickDensity($clusterPick),
                'travel_optimization' => $this->analyzeTravelOptimization($clusterPick),
                'consolidation_benefits' => $this->analyzeConsolidationBenefits($clusterPick)
            ],
            'progress_metrics' => [
                'completion_percentage' => $this->calculateCompletionPercentage($clusterPick),
                'items_per_order' => $clusterPick->total_items / $clusterPick->total_orders,
                'average_pick_time_per_item' => $this->calculateAveragePickTimePerItem($clusterPick)
            ],
            'optimization_analysis' => $clusterPick->optimization_data ?? []
        ];
    }

    private function calculateCurrentPickDensity($clusterPick): float
    {
        $totalItems = $clusterPick->items()->count();
        $uniqueLocations = $clusterPick->items()->distinct('location_id')->count();
        
        return $uniqueLocations > 0 ? $totalItems / $uniqueLocations : 0;
    }

    private function analyzeTravelOptimization($clusterPick): array
    {
        $items = $clusterPick->items()->with('location')->orderBy('sequence_number')->get();
        $totalDistance = 0;
        $zoneChanges = 0;
        $currentZone = null;

        foreach ($items as $item) {
            if ($item->location && $item->location->zone_id !== $currentZone) {
                $zoneChanges++;
                $currentZone = $item->location->zone_id;
            }
        }

        return [
            'estimated_travel_distance' => $totalDistance,
            'zone_changes' => $zoneChanges,
            'optimization_score' => max(0, 100 - ($zoneChanges * 10))
        ];
    }

    private function analyzeConsolidationBenefits($clusterPick): array
    {
        $orders = $clusterPick->orders()->with('salesOrder.customer')->get();
        $uniqueCustomers = $orders->pluck('salesOrder.customer_id')->unique()->count();
        
        return [
            'customer_consolidation_rate' => $clusterPick->total_orders > 0 
                ? (($clusterPick->total_orders - $uniqueCustomers) / $clusterPick->total_orders) * 100 
                : 0,
            'estimated_time_savings' => ($clusterPick->total_orders - $uniqueCustomers) * 2, // 2 minutes per consolidated order
            'packaging_efficiency' => $this->calculatePackagingEfficiency($clusterPick)
        ];
    }

    private function calculatePackagingEfficiency($clusterPick): float
    {
        // Simplified packaging efficiency calculation
        return 85.0; // Placeholder
    }

    private function calculateCompletionPercentage($clusterPick): float
    {
        $totalItems = $clusterPick->items()->count();
        $completedItems = $clusterPick->items()->where('status', 'completed')->count();
        
        return $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0;
    }

    private function calculateAveragePickTimePerItem($clusterPick): float
    {
        if (!$clusterPick->actual_pick_time || $clusterPick->total_items === 0) {
            return 0;
        }
        
        return $clusterPick->actual_pick_time / $clusterPick->total_items;
    }

    private function handlePickerAssignment($clusterPick, $oldPicker, $newPicker): void
    {
        if ($newPicker && $clusterPick->status === 'created') {
            $clusterPick->update(['status' => 'assigned']);
        }

        // Fire picker assignment event
        $this->fireTransactionalEvent('outbound.cluster_pick.picker_assigned', [
            'cluster_pick_id' => $clusterPick->id,
            'cluster_number' => $clusterPick->cluster_number,
            'old_picker_id' => $oldPicker,
            'new_picker_id' => $newPicker,
            'assigned_by' => auth()->id()
        ]);
    }

    private function handleStatusChange($clusterPick, $oldStatus, $newStatus): void
    {
        // Handle status-specific logic
        $statusTimestamps = [
            'assigned' => ['assigned_at' => now()],
            'in_progress' => ['started_at' => now()],
            'completed' => ['completed_at' => now()],
            'cancelled' => ['cancelled_at' => now()]
        ];

        if (isset($statusTimestamps[$newStatus])) {
            $clusterPick->update($statusTimestamps[$newStatus]);
        }

        // Handle order status updates for cancellation
        if ($newStatus === 'cancelled') {
            foreach ($clusterPick->orders as $clusterOrder) {
                $clusterOrder->salesOrder->update(['status' => $clusterOrder->original_status]);
            }
        }

        // Fire status change event
        $this->fireTransactionalEvent('outbound.cluster_pick.status_changed', [
            'cluster_pick_id' => $clusterPick->id,
            'cluster_number' => $clusterPick->cluster_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);
    }

    private function createMobilePickTasks($clusterPick): void
    {
        // Create mobile pick tasks for handheld devices
        // This would integrate with your mobile task management system
    }

    private function processPickExceptions($clusterPick, $exceptions): void
    {
        foreach ($exceptions as $exception) {
            $item = ClusterPickItem::find($exception['item_id']);
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

    private function calculateCompletionMetrics($clusterPick): array
    {
        $items = $clusterPick->items;
        $totalRequired = $items->sum('required_quantity');
        $totalPicked = $items->sum('picked_quantity');
        
        return [
            'total_items' => $items->count(),
            'completed_items' => $items->where('status', 'completed')->count(),
            'short_picked_items' => $items->where('status', 'short_picked')->count(),
            'total_required_quantity' => $totalRequired,
            'total_picked_quantity' => $totalPicked,
            'pick_accuracy' => $totalRequired > 0 ? ($totalPicked / $totalRequired) * 100 : 0,
            'cluster_efficiency' => $this->calculateActualClusterEfficiency($clusterPick)
        ];
    }

    private function calculateActualClusterEfficiency($clusterPick): float
    {
        $estimatedTime = $clusterPick->estimated_pick_time;
        $actualTime = $clusterPick->actual_pick_time;
        
        if (!$actualTime || !$estimatedTime) {
            return 0;
        }
        
        return ($estimatedTime / $actualTime) * 100;
    }

    private function updateOrderStatuses($clusterPick): void
    {
        foreach ($clusterPick->orders as $clusterOrder) {
            $salesOrder = $clusterOrder->salesOrder;
            
            // Check if all items for this order are picked
            $orderItems = $clusterPick->items()->where('sales_order_id', $salesOrder->id)->get();
            $allItemsPicked = $orderItems->every(function ($item) {
                return in_array($item->status, ['completed', 'short_picked']);
            });
            
            if ($allItemsPicked) {
                $salesOrder->update(['status' => 'picked']);
            }
        }
    }

    private function generateOrderPickSummaries($clusterPick): void
    {
        foreach ($clusterPick->orders as $clusterOrder) {
            $orderItems = $clusterPick->items()->where('sales_order_id', $clusterOrder->sales_order_id)->get();
            
            $summary = [
                'total_items' => $orderItems->count(),
                'completed_items' => $orderItems->where('status', 'completed')->count(),
                'short_picked_items' => $orderItems->where('status', 'short_picked')->count(),
                'total_required' => $orderItems->sum('required_quantity'),
                'total_picked' => $orderItems->sum('picked_quantity'),
                'pick_accuracy' => $orderItems->sum('required_quantity') > 0 
                    ? ($orderItems->sum('picked_quantity') / $orderItems->sum('required_quantity')) * 100 
                    : 0
            ];
            
            $clusterOrder->update(['pick_summary' => $summary]);
        }
    }

    private function performOptimization($clusterPick, $type, $constraints, $forceReoptimize): array
    {
        // This would implement various optimization algorithms
        // For now, return a simplified optimization result
        
        $items = $clusterPick->items()->with(['location', 'product'])->get();
        $optimizedSequence = $this->optimizeSequence($items, $type);
        
        return [
            'optimization_type' => $type,
            'efficiency_score' => 85,
            'estimated_time' => $this->calculateOptimizedTime($optimizedSequence),
            'pick_sequence' => $optimizedSequence,
            'efficiency_improvement' => 15,
            'travel_distance_reduction' => 25,
            'optimization_timestamp' => now()
        ];
    }

    private function optimizeSequence($items, $type): array
    {
        // Implement different optimization strategies
        switch ($type) {
            case 'distance':
                return $this->optimizeByDistance($items);
            case 'time':
                return $this->optimizeByTime($items);
            case 'priority':
                return $this->optimizeByPriority($items);
            default:
                return $this->optimizeMixed($items);
        }
    }

    private function optimizeByDistance($items): array
    {
        // Sort by location to minimize travel distance
        return $items->sortBy('location.aisle')->values()->map(function ($item, $index) {
            return [
                'item_id' => $item->id,
                'sequence' => $index + 1,
                'location_id' => $item->location_id,
                'optimization_score' => 90
            ];
        })->toArray();
    }

    private function optimizeByTime($items): array
    {
        // Optimize for fastest picking time
        return $items->sortBy('product.pick_time')->values()->map(function ($item, $index) {
            return [
                'item_id' => $item->id,
                'sequence' => $index + 1,
                'location_id' => $item->location_id,
                'optimization_score' => 85
            ];
        })->toArray();
    }

    private function optimizeByPriority($items): array
    {
        // Sort by order priority
        return $items->sortByDesc('clusterOrder.priority_score')->values()->map(function ($item, $index) {
            return [
                'item_id' => $item->id,
                'sequence' => $index + 1,
                'location_id' => $item->location_id,
                'optimization_score' => 80
            ];
        })->toArray();
    }

    private function optimizeMixed($items): array
    {
        // Mixed optimization considering multiple factors
        return $items->sortBy(function ($item) {
            return $item->location->aisle . '-' . $item->product->pick_time;
        })->values()->map(function ($item, $index) {
            return [
                'item_id' => $item->id,
                'sequence' => $index + 1,
                'location_id' => $item->location_id,
                'optimization_score' => 88
            ];
        })->toArray();
    }

    private function calculateOptimizedTime($sequence): int
    {
        // Calculate estimated time based on optimized sequence
        return count($sequence) * 0.7; // 42 seconds per item with optimization
    }

    private function updateItemSequences($clusterPick, $sequence): void
    {
        foreach ($sequence as $sequenceItem) {
            ClusterPickItem::where('id', $sequenceItem['item_id'])
                ->update(['sequence_number' => $sequenceItem['sequence']]);
        }
    }

    /**
     * Analytics helper methods
     */
    private function getPickerPerformance($query): array
    {
        return $query->with('assignedPicker')
            ->whereNotNull('assigned_picker_id')
            ->whereNotNull('actual_pick_time')
            ->selectRaw('assigned_picker_id, count(*) as cluster_count, avg(actual_pick_time) as avg_time, avg(cluster_efficiency_score) as avg_efficiency')
            ->groupBy('assigned_picker_id')
            ->get()
            ->map(function ($item) {
                return [
                    'picker' => $item->assignedPicker,
                    'cluster_count' => $item->cluster_count,
                    'avg_time' => $item->avg_time,
                    'avg_efficiency' => $item->avg_efficiency
                ];
            })
            ->toArray();
    }

    private function getEfficiencyMetrics($query): array
    {
        $completed = $query->where('status', 'completed');
        
        return [
            'completion_rate' => $query->count() > 0 ? ($completed->count() / $query->count()) * 100 : 0,
            'average_efficiency_score' => $completed->avg('cluster_efficiency_score') ?? 0,
            'time_accuracy' => $this->calculateTimeAccuracy($completed),
            'pick_accuracy' => $this->calculateOverallPickAccuracy($completed)
        ];
    }

    private function calculateTimeAccuracy($query): float
    {
        $items = $query->whereNotNull('actual_pick_time')->get();
        $accurateCount = 0;
        
        foreach ($items as $item) {
            $variance = abs($item->actual_pick_time - $item->estimated_pick_time);
            $tolerance = $item->estimated_pick_time * 0.2; // 20% tolerance
            
            if ($variance <= $tolerance) {
                $accurateCount++;
            }
        }
        
        return $items->count() > 0 ? ($accurateCount / $items->count()) * 100 : 0;
    }

    private function calculateOverallPickAccuracy($query): float
    {
        $clusterPickIds = $query->pluck('id');
        $items = ClusterPickItem::whereIn('cluster_pick_id', $clusterPickIds);
        
        $totalRequired = $items->sum('required_quantity');
        $totalPicked = $items->sum('picked_quantity');
        
        return $totalRequired > 0 ? ($totalPicked / $totalRequired) * 100 : 0;
    }

    private function getOptimizationImpact($query): array
    {
        $optimized = $query->whereNotNull('optimization_data');
        
        return [
            'optimization_usage_rate' => $query->count() > 0 ? ($optimized->count() / $query->count()) * 100 : 0,
            'average_efficiency_improvement' => $this->calculateAverageEfficiencyImprovement($optimized),
            'time_savings' => $this->calculateTimeSavings($optimized),
            'distance_reduction' => $this->calculateDistanceReduction($optimized)
        ];
    }

    private function calculateAverageEfficiencyImprovement($query): float
    {
        return $query->get()->avg(function ($item) {
            $data = $item->optimization_data;
            return $data['efficiency_improvement'] ?? 0;
        }) ?? 0;
    }

    private function calculateTimeSavings($query): float
    {
        return $query->get()->sum(function ($item) {
            $estimated = $item->estimated_pick_time;
            $actual = $item->actual_pick_time;
            return $actual ? max(0, $estimated - $actual) : 0;
        });
    }

    private function calculateDistanceReduction($query): float
    {
        return $query->get()->avg(function ($item) {
            $data = $item->optimization_data;
            return $data['travel_distance_reduction'] ?? 0;
        }) ?? 0;
    }

    private function getClusterTrends($dateFrom, $dateTo, $warehouseId): array
    {
        $query = ClusterPick::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, count(*) as cluster_count, avg(total_orders) as avg_size, avg(actual_pick_time) as avg_time')
            ->groupBy('date')
            ->orderBy('date');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->toArray();
    }
}