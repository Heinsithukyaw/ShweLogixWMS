<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\OrderConsolidation;
use App\Models\Outbound\OrderConsolidationRule;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class OrderConsolidationController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of order consolidations
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrderConsolidation::with(['warehouse', 'orders.customer', 'createdBy']);

        // Apply filters
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('consolidation_type')) {
            $query->where('consolidation_type', $request->consolidation_type);
        }

        if ($request->has('customer_id')) {
            $query->whereHas('orders', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $consolidations = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $consolidations,
            'message' => 'Order consolidations retrieved successfully'
        ]);
    }

    /**
     * Store a newly created order consolidation
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'consolidation_type' => 'required|in:customer,address,route,carrier,date,manual',
            'consolidation_criteria' => 'required|array',
            'orders' => 'required|array|min:2',
            'orders.*' => 'exists:sales_orders,id',
            'target_ship_date' => 'nullable|date',
            'priority_level' => 'required|in:low,medium,high,urgent',
            'consolidation_window_hours' => 'nullable|integer|min:1|max:168',
            'max_orders' => 'nullable|integer|min:2|max:100',
            'max_weight' => 'nullable|numeric|min:0',
            'max_volume' => 'nullable|numeric|min:0',
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

            // Validate orders can be consolidated
            $orders = SalesOrder::whereIn('id', $request->orders)
                ->where('warehouse_id', $request->warehouse_id)
                ->whereIn('status', ['allocated', 'picked'])
                ->get();

            if ($orders->count() !== count($request->orders)) {
                throw new \Exception('Some orders are not eligible for consolidation');
            }

            // Validate consolidation criteria
            $this->validateConsolidationCriteria($orders, $request->consolidation_type, $request->consolidation_criteria);

            // Generate consolidation number
            $consolidationNumber = $this->generateConsolidationNumber();

            // Calculate consolidation metrics
            $metrics = $this->calculateConsolidationMetrics($orders);

            // Create consolidation
            $consolidation = OrderConsolidation::create([
                'consolidation_number' => $consolidationNumber,
                'warehouse_id' => $request->warehouse_id,
                'consolidation_type' => $request->consolidation_type,
                'consolidation_criteria' => $request->consolidation_criteria,
                'status' => 'created',
                'priority_level' => $request->priority_level,
                'target_ship_date' => $request->target_ship_date,
                'consolidation_window_hours' => $request->consolidation_window_hours ?? 24,
                'max_orders' => $request->max_orders ?? 50,
                'max_weight' => $request->max_weight,
                'max_volume' => $request->max_volume,
                'total_orders' => $orders->count(),
                'total_items' => $metrics['total_items'],
                'total_weight' => $metrics['total_weight'],
                'total_volume' => $metrics['total_volume'],
                'total_value' => $metrics['total_value'],
                'estimated_savings' => $this->calculateEstimatedSavings($orders, $request->consolidation_type),
                'consolidation_score' => $this->calculateConsolidationScore($orders, $request->consolidation_criteria),
                'notes' => $request->notes,
                'created_by' => auth()->id(),
                'expires_at' => now()->addHours($request->consolidation_window_hours ?? 24)
            ]);

            // Add orders to consolidation
            foreach ($orders as $order) {
                $consolidation->orders()->attach($order->id, [
                    'original_status' => $order->status,
                    'order_value' => $order->total_amount,
                    'order_weight' => $order->total_weight ?? 0,
                    'order_volume' => $order->total_volume ?? 0,
                    'item_count' => $order->items->count(),
                    'added_at' => now()
                ]);

                // Update order status
                $order->update([
                    'status' => 'consolidated',
                    'consolidation_id' => $consolidation->id
                ]);
            }

            // Apply consolidation optimizations
            $this->applyConsolidationOptimizations($consolidation);

            // Fire event
            $this->fireTransactionalEvent('outbound.order.consolidated', [
                'consolidation_id' => $consolidation->id,
                'consolidation_number' => $consolidationNumber,
                'warehouse_id' => $request->warehouse_id,
                'consolidation_type' => $request->consolidation_type,
                'order_count' => $orders->count(),
                'total_value' => $metrics['total_value'],
                'estimated_savings' => $consolidation->estimated_savings
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $consolidation->load(['warehouse', 'orders.customer']),
                'message' => 'Order consolidation created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order consolidation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order consolidation
     */
    public function show($id): JsonResponse
    {
        $consolidation = OrderConsolidation::with([
            'warehouse',
            'orders.customer',
            'orders.items.product',
            'createdBy'
        ])->find($id);

        if (!$consolidation) {
            return response()->json([
                'success' => false,
                'message' => 'Order consolidation not found'
            ], 404);
        }

        // Get consolidation analysis
        $analysis = $this->getConsolidationAnalysis($consolidation);

        return response()->json([
            'success' => true,
            'data' => array_merge($consolidation->toArray(), [
                'analysis' => $analysis
            ]),
            'message' => 'Order consolidation retrieved successfully'
        ]);
    }

    /**
     * Update the specified order consolidation
     */
    public function update(Request $request, $id): JsonResponse
    {
        $consolidation = OrderConsolidation::find($id);

        if (!$consolidation) {
            return response()->json([
                'success' => false,
                'message' => 'Order consolidation not found'
            ], 404);
        }

        if (!in_array($consolidation->status, ['created', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update consolidation in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'target_ship_date' => 'nullable|date',
            'priority_level' => 'sometimes|in:low,medium,high,urgent',
            'consolidation_window_hours' => 'nullable|integer|min:1|max:168',
            'max_weight' => 'nullable|numeric|min:0',
            'max_volume' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:created,pending,approved,processing,completed,cancelled'
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

            $oldStatus = $consolidation->status;

            $consolidation->update($request->only([
                'target_ship_date',
                'priority_level',
                'consolidation_window_hours',
                'max_weight',
                'max_volume',
                'notes',
                'status'
            ]));

            // Update expiration if window changed
            if ($request->has('consolidation_window_hours')) {
                $consolidation->update([
                    'expires_at' => now()->addHours($request->consolidation_window_hours)
                ]);
            }

            // Handle status changes
            if ($request->has('status') && $oldStatus !== $request->status) {
                $this->handleStatusChange($consolidation, $oldStatus, $request->status);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $consolidation->load(['warehouse', 'orders.customer']),
                'message' => 'Order consolidation updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order consolidation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add orders to existing consolidation
     */
    public function addOrders(Request $request, $id): JsonResponse
    {
        $consolidation = OrderConsolidation::find($id);

        if (!$consolidation) {
            return response()->json([
                'success' => false,
                'message' => 'Order consolidation not found'
            ], 404);
        }

        if (!in_array($consolidation->status, ['created', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add orders to consolidation in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'orders' => 'required|array|min:1',
            'orders.*' => 'exists:sales_orders,id'
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

            // Get new orders
            $newOrders = SalesOrder::whereIn('id', $request->orders)
                ->where('warehouse_id', $consolidation->warehouse_id)
                ->whereIn('status', ['allocated', 'picked'])
                ->whereNotIn('id', $consolidation->orders->pluck('id'))
                ->get();

            if ($newOrders->isEmpty()) {
                throw new \Exception('No eligible orders found to add');
            }

            // Check consolidation limits
            $currentOrderCount = $consolidation->orders->count();
            if (($currentOrderCount + $newOrders->count()) > $consolidation->max_orders) {
                throw new \Exception('Adding these orders would exceed maximum order limit');
            }

            // Validate consolidation criteria for new orders
            $allOrders = $consolidation->orders->concat($newOrders);
            $this->validateConsolidationCriteria($allOrders, $consolidation->consolidation_type, $consolidation->consolidation_criteria);

            // Add orders to consolidation
            foreach ($newOrders as $order) {
                $consolidation->orders()->attach($order->id, [
                    'original_status' => $order->status,
                    'order_value' => $order->total_amount,
                    'order_weight' => $order->total_weight ?? 0,
                    'order_volume' => $order->total_volume ?? 0,
                    'item_count' => $order->items->count(),
                    'added_at' => now()
                ]);

                $order->update([
                    'status' => 'consolidated',
                    'consolidation_id' => $consolidation->id
                ]);
            }

            // Recalculate metrics
            $this->recalculateConsolidationMetrics($consolidation);

            // Fire event
            $this->fireTransactionalEvent('outbound.consolidation.orders_added', [
                'consolidation_id' => $consolidation->id,
                'consolidation_number' => $consolidation->consolidation_number,
                'added_order_count' => $newOrders->count(),
                'total_order_count' => $consolidation->orders()->count(),
                'added_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $consolidation->load(['warehouse', 'orders.customer']),
                'message' => "Successfully added {$newOrders->count()} orders to consolidation"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process consolidation (start picking/packing)
     */
    public function process(Request $request, $id): JsonResponse
    {
        $consolidation = OrderConsolidation::find($id);

        if (!$consolidation) {
            return response()->json([
                'success' => false,
                'message' => 'Order consolidation not found'
            ], 404);
        }

        if ($consolidation->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Consolidation must be approved before processing'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'processing_method' => 'required|in:batch_pick,wave_pick,zone_pick,sequential',
            'assigned_picker_id' => 'nullable|exists:employees,id',
            'processing_notes' => 'nullable|string'
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

            $consolidation->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'processing_method' => $request->processing_method,
                'assigned_picker_id' => $request->assigned_picker_id,
                'processing_notes' => $request->processing_notes
            ]);

            // Create processing tasks based on method
            $this->createProcessingTasks($consolidation, $request->processing_method);

            // Update order statuses
            foreach ($consolidation->orders as $order) {
                $order->update(['status' => 'processing']);
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.consolidation.processing_started', [
                'consolidation_id' => $consolidation->id,
                'consolidation_number' => $consolidation->consolidation_number,
                'processing_method' => $request->processing_method,
                'assigned_picker_id' => $request->assigned_picker_id,
                'order_count' => $consolidation->orders->count()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $consolidation->load(['warehouse', 'orders.customer']),
                'message' => 'Consolidation processing started successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to start processing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get consolidation suggestions
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'consolidation_types' => 'nullable|array',
            'consolidation_types.*' => 'in:customer,address,route,carrier,date',
            'max_suggestions' => 'nullable|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $suggestions = $this->generateConsolidationSuggestions(
                $request->warehouse_id,
                $request->consolidation_types ?? ['customer', 'address', 'route'],
                $request->max_suggestions ?? 20
            );

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'message' => 'Consolidation suggestions generated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate suggestions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get consolidation analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = OrderConsolidation::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $analytics = [
            'total_consolidations' => $query->count(),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_type' => $query->groupBy('consolidation_type')->selectRaw('consolidation_type, count(*) as count')->pluck('count', 'consolidation_type'),
            'total_orders_consolidated' => $query->sum('total_orders'),
            'total_savings' => $query->sum('estimated_savings'),
            'average_consolidation_size' => $query->avg('total_orders'),
            'average_savings_per_consolidation' => $query->avg('estimated_savings'),
            'consolidation_efficiency' => $this->calculateConsolidationEfficiency($query),
            'top_consolidation_criteria' => $this->getTopConsolidationCriteria($query),
            'consolidation_trends' => $this->getConsolidationTrends($dateFrom, $dateTo, $warehouseId)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Consolidation analytics retrieved successfully'
        ]);
    }

    /**
     * Helper methods
     */
    private function generateConsolidationNumber(): string
    {
        $year = date('Y');
        $sequence = OrderConsolidation::whereYear('created_at', $year)->count() + 1;
        
        return 'CON-' . $year . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    private function validateConsolidationCriteria($orders, $type, $criteria): void
    {
        switch ($type) {
            case 'customer':
                $customerIds = $orders->pluck('customer_id')->unique();
                if ($customerIds->count() > 1) {
                    throw new \Exception('Orders must be from the same customer for customer consolidation');
                }
                break;

            case 'address':
                $addresses = $orders->pluck('shipping_address')->unique();
                if ($addresses->count() > 1) {
                    throw new \Exception('Orders must have the same shipping address for address consolidation');
                }
                break;

            case 'route':
                // Validate route compatibility
                if (!$this->areOrdersRouteCompatible($orders, $criteria)) {
                    throw new \Exception('Orders are not compatible for route consolidation');
                }
                break;

            case 'carrier':
                $carriers = $orders->pluck('preferred_carrier_id')->unique();
                if ($carriers->count() > 1) {
                    throw new \Exception('Orders must use the same carrier for carrier consolidation');
                }
                break;

            case 'date':
                $shipDates = $orders->pluck('requested_ship_date')->unique();
                if ($shipDates->count() > 1) {
                    throw new \Exception('Orders must have the same ship date for date consolidation');
                }
                break;
        }
    }

    private function calculateConsolidationMetrics($orders): array
    {
        return [
            'total_items' => $orders->sum(function ($order) {
                return $order->items->sum('quantity');
            }),
            'total_weight' => $orders->sum('total_weight'),
            'total_volume' => $orders->sum('total_volume'),
            'total_value' => $orders->sum('total_amount')
        ];
    }

    private function calculateEstimatedSavings($orders, $consolidationType): float
    {
        // Calculate estimated savings based on consolidation type
        $baseSavings = 0;
        $orderCount = $orders->count();

        switch ($consolidationType) {
            case 'customer':
                $baseSavings = ($orderCount - 1) * 5.00; // $5 per order saved
                break;
            case 'address':
                $baseSavings = ($orderCount - 1) * 8.00; // $8 per order saved
                break;
            case 'route':
                $baseSavings = ($orderCount - 1) * 12.00; // $12 per order saved
                break;
            case 'carrier':
                $baseSavings = ($orderCount - 1) * 3.00; // $3 per order saved
                break;
            default:
                $baseSavings = ($orderCount - 1) * 2.00; // $2 per order saved
        }

        // Add volume-based savings
        $totalValue = $orders->sum('total_amount');
        $volumeSavings = $totalValue * 0.02; // 2% of total value

        return $baseSavings + $volumeSavings;
    }

    private function calculateConsolidationScore($orders, $criteria): int
    {
        $score = 50; // Base score

        // Order count factor
        $orderCount = $orders->count();
        $score += min(30, $orderCount * 5);

        // Value factor
        $totalValue = $orders->sum('total_amount');
        if ($totalValue > 1000) $score += 10;
        if ($totalValue > 5000) $score += 10;

        // Compatibility factor
        $score += $this->calculateCompatibilityScore($orders, $criteria);

        return min(100, max(0, $score));
    }

    private function calculateCompatibilityScore($orders, $criteria): int
    {
        $score = 0;

        // Same customer bonus
        if ($orders->pluck('customer_id')->unique()->count() === 1) {
            $score += 15;
        }

        // Same address bonus
        if ($orders->pluck('shipping_address')->unique()->count() === 1) {
            $score += 10;
        }

        // Similar ship dates bonus
        $shipDates = $orders->pluck('requested_ship_date')->filter();
        if ($shipDates->isNotEmpty()) {
            $dateRange = $shipDates->max()->diffInDays($shipDates->min());
            if ($dateRange <= 1) $score += 10;
            elseif ($dateRange <= 3) $score += 5;
        }

        return $score;
    }

    private function applyConsolidationOptimizations($consolidation): void
    {
        // Apply various optimizations based on consolidation type
        switch ($consolidation->consolidation_type) {
            case 'route':
                $this->optimizeRouteConsolidation($consolidation);
                break;
            case 'customer':
                $this->optimizeCustomerConsolidation($consolidation);
                break;
            // Add other optimization types as needed
        }
    }

    private function optimizeRouteConsolidation($consolidation): void
    {
        // Optimize route-based consolidation
        // This would integrate with route optimization algorithms
    }

    private function optimizeCustomerConsolidation($consolidation): void
    {
        // Optimize customer-based consolidation
        // This could include priority adjustments, packaging optimizations, etc.
    }

    private function handleStatusChange($consolidation, $oldStatus, $newStatus): void
    {
        // Handle status-specific logic
        $statusTimestamps = [
            'approved' => ['approved_at' => now(), 'approved_by' => auth()->id()],
            'processing' => ['processing_started_at' => now()],
            'completed' => ['completed_at' => now()],
            'cancelled' => ['cancelled_at' => now(), 'cancelled_by' => auth()->id()]
        ];

        if (isset($statusTimestamps[$newStatus])) {
            $consolidation->update($statusTimestamps[$newStatus]);
        }

        // Handle order status updates
        if ($newStatus === 'cancelled') {
            foreach ($consolidation->orders as $order) {
                $originalStatus = $consolidation->orders()->where('sales_order_id', $order->id)->first()->pivot->original_status;
                $order->update(['status' => $originalStatus, 'consolidation_id' => null]);
            }
        }

        // Fire status change event
        $this->fireTransactionalEvent('outbound.consolidation.status_changed', [
            'consolidation_id' => $consolidation->id,
            'consolidation_number' => $consolidation->consolidation_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);
    }

    private function recalculateConsolidationMetrics($consolidation): void
    {
        $orders = $consolidation->orders;
        $metrics = $this->calculateConsolidationMetrics($orders);

        $consolidation->update([
            'total_orders' => $orders->count(),
            'total_items' => $metrics['total_items'],
            'total_weight' => $metrics['total_weight'],
            'total_volume' => $metrics['total_volume'],
            'total_value' => $metrics['total_value'],
            'estimated_savings' => $this->calculateEstimatedSavings($orders, $consolidation->consolidation_type),
            'consolidation_score' => $this->calculateConsolidationScore($orders, $consolidation->consolidation_criteria)
        ]);
    }

    private function createProcessingTasks($consolidation, $method): void
    {
        // Create appropriate processing tasks based on method
        switch ($method) {
            case 'batch_pick':
                $this->createBatchPickTasks($consolidation);
                break;
            case 'wave_pick':
                $this->createWavePickTasks($consolidation);
                break;
            case 'zone_pick':
                $this->createZonePickTasks($consolidation);
                break;
            case 'sequential':
                $this->createSequentialTasks($consolidation);
                break;
        }
    }

    private function createBatchPickTasks($consolidation): void
    {
        // Create batch pick tasks for the consolidation
        // This would integrate with your batch picking system
    }

    private function createWavePickTasks($consolidation): void
    {
        // Create wave pick tasks for the consolidation
        // This would integrate with your wave picking system
    }

    private function createZonePickTasks($consolidation): void
    {
        // Create zone pick tasks for the consolidation
        // This would integrate with your zone picking system
    }

    private function createSequentialTasks($consolidation): void
    {
        // Create sequential processing tasks
        // This would create tasks in order priority sequence
    }

    private function getConsolidationAnalysis($consolidation): array
    {
        return [
            'efficiency_score' => $consolidation->consolidation_score,
            'savings_analysis' => [
                'estimated_savings' => $consolidation->estimated_savings,
                'savings_per_order' => $consolidation->total_orders > 0 
                    ? $consolidation->estimated_savings / $consolidation->total_orders 
                    : 0,
                'cost_reduction_percentage' => $consolidation->total_value > 0 
                    ? ($consolidation->estimated_savings / $consolidation->total_value) * 100 
                    : 0
            ],
            'consolidation_metrics' => [
                'orders_per_consolidation' => $consolidation->total_orders,
                'items_per_consolidation' => $consolidation->total_items,
                'average_order_value' => $consolidation->total_orders > 0 
                    ? $consolidation->total_value / $consolidation->total_orders 
                    : 0,
                'weight_efficiency' => $this->calculateWeightEfficiency($consolidation),
                'volume_efficiency' => $this->calculateVolumeEfficiency($consolidation)
            ],
            'processing_analysis' => [
                'estimated_processing_time' => $this->estimateProcessingTime($consolidation),
                'complexity_score' => $this->calculateComplexityScore($consolidation),
                'resource_requirements' => $this->getResourceRequirements($consolidation)
            ]
        ];
    }

    private function generateConsolidationSuggestions($warehouseId, $types, $maxSuggestions): array
    {
        $suggestions = [];

        // Get eligible orders
        $eligibleOrders = SalesOrder::where('warehouse_id', $warehouseId)
            ->whereIn('status', ['allocated', 'picked'])
            ->whereNull('consolidation_id')
            ->with(['customer', 'items'])
            ->get();

        foreach ($types as $type) {
            $typeSuggestions = $this->generateSuggestionsByType($eligibleOrders, $type);
            $suggestions = array_merge($suggestions, $typeSuggestions);
        }

        // Sort by potential savings and limit results
        usort($suggestions, function ($a, $b) {
            return $b['estimated_savings'] <=> $a['estimated_savings'];
        });

        return array_slice($suggestions, 0, $maxSuggestions);
    }

    private function generateSuggestionsByType($orders, $type): array
    {
        $suggestions = [];

        switch ($type) {
            case 'customer':
                $suggestions = $this->generateCustomerConsolidationSuggestions($orders);
                break;
            case 'address':
                $suggestions = $this->generateAddressConsolidationSuggestions($orders);
                break;
            case 'route':
                $suggestions = $this->generateRouteConsolidationSuggestions($orders);
                break;
            // Add other types as needed
        }

        return $suggestions;
    }

    private function generateCustomerConsolidationSuggestions($orders): array
    {
        $suggestions = [];
        $ordersByCustomer = $orders->groupBy('customer_id');

        foreach ($ordersByCustomer as $customerId => $customerOrders) {
            if ($customerOrders->count() >= 2) {
                $metrics = $this->calculateConsolidationMetrics($customerOrders);
                $suggestions[] = [
                    'type' => 'customer',
                    'criteria' => ['customer_id' => $customerId],
                    'orders' => $customerOrders->pluck('id')->toArray(),
                    'order_count' => $customerOrders->count(),
                    'estimated_savings' => $this->calculateEstimatedSavings($customerOrders, 'customer'),
                    'consolidation_score' => $this->calculateConsolidationScore($customerOrders, ['customer_id' => $customerId]),
                    'metrics' => $metrics,
                    'customer' => $customerOrders->first()->customer
                ];
            }
        }

        return $suggestions;
    }

    private function generateAddressConsolidationSuggestions($orders): array
    {
        $suggestions = [];
        $ordersByAddress = $orders->groupBy('shipping_address');

        foreach ($ordersByAddress as $address => $addressOrders) {
            if ($addressOrders->count() >= 2) {
                $metrics = $this->calculateConsolidationMetrics($addressOrders);
                $suggestions[] = [
                    'type' => 'address',
                    'criteria' => ['shipping_address' => $address],
                    'orders' => $addressOrders->pluck('id')->toArray(),
                    'order_count' => $addressOrders->count(),
                    'estimated_savings' => $this->calculateEstimatedSavings($addressOrders, 'address'),
                    'consolidation_score' => $this->calculateConsolidationScore($addressOrders, ['shipping_address' => $address]),
                    'metrics' => $metrics,
                    'address' => $address
                ];
            }
        }

        return $suggestions;
    }

    private function generateRouteConsolidationSuggestions($orders): array
    {
        // This would implement route-based consolidation logic
        // For now, return empty array as it requires complex routing algorithms
        return [];
    }

    // Additional helper methods for analysis
    private function areOrdersRouteCompatible($orders, $criteria): bool
    {
        // Implement route compatibility logic
        return true; // Placeholder
    }

    private function calculateWeightEfficiency($consolidation): float
    {
        // Calculate weight efficiency metric
        return 85.0; // Placeholder
    }

    private function calculateVolumeEfficiency($consolidation): float
    {
        // Calculate volume efficiency metric
        return 78.0; // Placeholder
    }

    private function estimateProcessingTime($consolidation): int
    {
        // Estimate processing time in minutes
        return $consolidation->total_items * 2; // 2 minutes per item
    }

    private function calculateComplexityScore($consolidation): int
    {
        // Calculate complexity score (0-100)
        $score = 50; // Base complexity
        $score += $consolidation->total_orders * 2; // Order complexity
        $score += ($consolidation->total_items / 10); // Item complexity
        
        return min(100, $score);
    }

    private function getResourceRequirements($consolidation): array
    {
        return [
            'estimated_pickers' => ceil($consolidation->total_items / 50),
            'estimated_packers' => ceil($consolidation->total_orders / 10),
            'equipment_needed' => ['handheld_scanner', 'packing_materials'],
            'estimated_space' => $consolidation->total_volume * 1.2 // 20% buffer
        ];
    }

    // Analytics helper methods
    private function calculateConsolidationEfficiency($query): float
    {
        $total = $query->count();
        $completed = $query->where('status', 'completed')->count();
        
        return $total > 0 ? ($completed / $total) * 100 : 0;
    }

    private function getTopConsolidationCriteria($query): array
    {
        return $query->selectRaw('consolidation_type, count(*) as count, avg(estimated_savings) as avg_savings')
            ->groupBy('consolidation_type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    private function getConsolidationTrends($dateFrom, $dateTo, $warehouseId): array
    {
        $query = OrderConsolidation::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, count(*) as consolidation_count, sum(total_orders) as order_count, sum(estimated_savings) as total_savings')
            ->groupBy('date')
            ->orderBy('date');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->toArray();
    }
}