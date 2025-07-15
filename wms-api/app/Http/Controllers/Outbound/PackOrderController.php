<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\PackOrder;
use App\Models\Outbound\PackOrderItem;
use App\Models\Outbound\PackingStation;
use App\Models\SalesOrder;
use App\Models\CartonType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class PackOrderController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of pack orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = PackOrder::with(['salesOrder.customer', 'packingStation', 'packer', 'cartonType']);

        // Apply filters
        if ($request->has('warehouse_id')) {
            $query->whereHas('salesOrder', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        if ($request->has('packing_station_id')) {
            $query->where('packing_station_id', $request->packing_station_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('packer_id')) {
            $query->where('packer_id', $request->packer_id);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $packOrders = $query->orderBy('priority_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $packOrders,
            'message' => 'Pack orders retrieved successfully'
        ]);
    }

    /**
     * Store a newly created pack order
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sales_order_id' => 'required|exists:sales_orders,id',
            'packing_station_id' => 'required|exists:packing_stations,id',
            'packer_id' => 'nullable|exists:employees,id',
            'carton_type_id' => 'nullable|exists:carton_types,id',
            'pack_method' => 'required|in:single_item,multi_item,fragile,hazmat,custom',
            'priority_level' => 'required|in:low,medium,high,urgent,rush',
            'special_instructions' => 'nullable|string',
            'pack_deadline' => 'nullable|date|after:now',
            'requires_gift_wrap' => 'boolean',
            'requires_signature' => 'boolean',
            'fragile_items' => 'boolean',
            'hazmat_items' => 'boolean',
            'custom_packaging' => 'nullable|array',
            'items' => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.quantity_to_pack' => 'required|integer|min:1',
            'items.*.special_handling' => 'nullable|string'
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

            // Validate sales order is ready for packing
            $salesOrder = SalesOrder::with('items')->find($request->sales_order_id);
            
            if (!in_array($salesOrder->status, ['picked', 'partially_packed'])) {
                throw new \Exception('Sales order is not ready for packing');
            }

            // Validate packing station availability
            $packingStation = PackingStation::find($request->packing_station_id);
            if (!$packingStation->is_active || $packingStation->status !== 'idle') {
                throw new \Exception('Packing station is not available');
            }

            // Generate pack order number
            $packOrderNumber = $this->generatePackOrderNumber();

            // Calculate pack order metrics
            $metrics = $this->calculatePackOrderMetrics($request->items, $salesOrder);

            // Determine optimal carton if not specified
            $cartonTypeId = $request->carton_type_id ?? $this->determineOptimalCarton($metrics);

            // Create pack order
            $packOrder = PackOrder::create([
                'pack_order_number' => $packOrderNumber,
                'sales_order_id' => $request->sales_order_id,
                'packing_station_id' => $request->packing_station_id,
                'packer_id' => $request->packer_id,
                'carton_type_id' => $cartonTypeId,
                'pack_method' => $request->pack_method,
                'status' => $request->packer_id ? 'assigned' : 'created',
                'priority_level' => $request->priority_level,
                'priority_score' => $this->calculatePriorityScore($salesOrder, $request->priority_level),
                'special_instructions' => $request->special_instructions,
                'pack_deadline' => $request->pack_deadline,
                'requires_gift_wrap' => $request->requires_gift_wrap ?? false,
                'requires_signature' => $request->requires_signature ?? false,
                'fragile_items' => $request->fragile_items ?? false,
                'hazmat_items' => $request->hazmat_items ?? false,
                'custom_packaging' => $request->custom_packaging ?? [],
                'total_items' => count($request->items),
                'total_quantity' => array_sum(array_column($request->items, 'quantity_to_pack')),
                'estimated_weight' => $metrics['estimated_weight'],
                'estimated_volume' => $metrics['estimated_volume'],
                'estimated_pack_time' => $this->estimatePackTime($metrics, $request->pack_method),
                'created_by' => auth()->id(),
                'packaging_requirements' => $this->generatePackagingRequirements($request->all(), $metrics)
            ]);

            // Create pack order items
            foreach ($request->items as $itemData) {
                $salesOrderItem = $salesOrder->items->find($itemData['sales_order_item_id']);
                
                PackOrderItem::create([
                    'pack_order_id' => $packOrder->id,
                    'sales_order_item_id' => $itemData['sales_order_item_id'],
                    'product_id' => $salesOrderItem->product_id,
                    'quantity_to_pack' => $itemData['quantity_to_pack'],
                    'quantity_packed' => 0,
                    'status' => 'pending',
                    'special_handling' => $itemData['special_handling'] ?? null,
                    'item_weight' => $salesOrderItem->weight ?? 0,
                    'item_dimensions' => $salesOrderItem->dimensions ?? [],
                    'fragile' => $salesOrderItem->product->is_fragile ?? false,
                    'hazmat' => $salesOrderItem->product->is_hazmat ?? false
                ]);
            }

            // Update packing station status
            $packingStation->update(['status' => 'assigned']);

            // Update sales order status
            $this->updateSalesOrderStatus($salesOrder, $packOrder);

            // Fire event
            $this->fireTransactionalEvent('outbound.pack_order.created', [
                'pack_order_id' => $packOrder->id,
                'pack_order_number' => $packOrderNumber,
                'sales_order_id' => $request->sales_order_id,
                'packing_station_id' => $request->packing_station_id,
                'packer_id' => $request->packer_id,
                'total_items' => $packOrder->total_items,
                'pack_method' => $request->pack_method
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $packOrder->load(['salesOrder.customer', 'packingStation', 'packer', 'cartonType']),
                'message' => 'Pack order created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create pack order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified pack order
     */
    public function show($id): JsonResponse
    {
        $packOrder = PackOrder::with([
            'salesOrder.customer',
            'packingStation',
            'packer',
            'cartonType',
            'items.product',
            'items.salesOrderItem',
            'createdBy'
        ])->find($id);

        if (!$packOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Pack order not found'
            ], 404);
        }

        // Get packing instructions
        $packingInstructions = $this->generatePackingInstructions($packOrder);

        // Get progress metrics
        $progress = $this->calculateProgress($packOrder);

        return response()->json([
            'success' => true,
            'data' => array_merge($packOrder->toArray(), [
                'packing_instructions' => $packingInstructions,
                'progress' => $progress
            ]),
            'message' => 'Pack order retrieved successfully'
        ]);
    }

    /**
     * Update the specified pack order
     */
    public function update(Request $request, $id): JsonResponse
    {
        $packOrder = PackOrder::find($id);

        if (!$packOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Pack order not found'
            ], 404);
        }

        if (!in_array($packOrder->status, ['created', 'assigned', 'in_progress'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update pack order in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'packer_id' => 'nullable|exists:employees,id',
            'carton_type_id' => 'nullable|exists:carton_types,id',
            'priority_level' => 'sometimes|in:low,medium,high,urgent,rush',
            'special_instructions' => 'nullable|string',
            'pack_deadline' => 'nullable|date',
            'custom_packaging' => 'nullable|array',
            'status' => 'sometimes|in:created,assigned,in_progress,packed,quality_check,completed,cancelled'
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

            $oldStatus = $packOrder->status;
            $oldPacker = $packOrder->packer_id;

            // Update pack order
            $updateData = $request->only([
                'packer_id',
                'carton_type_id',
                'priority_level',
                'special_instructions',
                'pack_deadline',
                'custom_packaging',
                'status'
            ]);

            // Recalculate priority score if priority level changed
            if ($request->has('priority_level')) {
                $updateData['priority_score'] = $this->calculatePriorityScore(
                    $packOrder->salesOrder,
                    $request->priority_level
                );
            }

            $packOrder->update($updateData);

            // Handle packer assignment changes
            if ($request->has('packer_id') && $oldPacker !== $request->packer_id) {
                $this->handlePackerAssignment($packOrder, $oldPacker, $request->packer_id);
            }

            // Handle status changes
            if ($request->has('status') && $oldStatus !== $request->status) {
                $this->handleStatusChange($packOrder, $oldStatus, $request->status);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $packOrder->load(['salesOrder.customer', 'packingStation', 'packer', 'cartonType']),
                'message' => 'Pack order updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update pack order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start packing process
     */
    public function startPacking(Request $request, $id): JsonResponse
    {
        $packOrder = PackOrder::find($id);

        if (!$packOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Pack order not found'
            ], 404);
        }

        if ($packOrder->status !== 'assigned') {
            return response()->json([
                'success' => false,
                'message' => 'Pack order must be assigned before starting'
            ], 400);
        }

        if (!$packOrder->packer_id) {
            return response()->json([
                'success' => false,
                'message' => 'No packer assigned to this pack order'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $packOrder->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'started_by' => auth()->id()
            ]);

            // Update packing station status
            $packOrder->packingStation->update(['status' => 'active']);

            // Fire event
            $this->fireTransactionalEvent('outbound.pack_order.started', [
                'pack_order_id' => $packOrder->id,
                'pack_order_number' => $packOrder->pack_order_number,
                'packer_id' => $packOrder->packer_id,
                'packing_station_id' => $packOrder->packing_station_id,
                'started_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $packOrder->load(['salesOrder.customer', 'packingStation', 'packer']),
                'message' => 'Packing started successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to start packing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete packing process
     */
    public function completePacking(Request $request, $id): JsonResponse
    {
        $packOrder = PackOrder::find($id);

        if (!$packOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Pack order not found'
            ], 404);
        }

        if ($packOrder->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Pack order is not in progress'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'actual_weight' => 'required|numeric|min:0',
            'actual_dimensions' => 'required|array',
            'actual_dimensions.length' => 'required|numeric|min:0',
            'actual_dimensions.width' => 'required|numeric|min:0',
            'actual_dimensions.height' => 'required|numeric|min:0',
            'carton_used' => 'required|exists:carton_types,id',
            'packing_materials' => 'nullable|array',
            'quality_notes' => 'nullable|string',
            'completion_notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:pack_order_items,id',
            'items.*.quantity_packed' => 'required|integer|min:0',
            'items.*.condition' => 'required|in:good,damaged,missing',
            'items.*.notes' => 'nullable|string'
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

            // Update pack order items
            foreach ($request->items as $itemData) {
                $packOrderItem = PackOrderItem::find($itemData['item_id']);
                $packOrderItem->update([
                    'quantity_packed' => $itemData['quantity_packed'],
                    'status' => $itemData['quantity_packed'] >= $packOrderItem->quantity_to_pack ? 'completed' : 'partial',
                    'condition' => $itemData['condition'],
                    'packing_notes' => $itemData['notes'] ?? null
                ]);
            }

            // Calculate packing metrics
            $packingMetrics = $this->calculatePackingMetrics($packOrder, $request->all());

            // Update pack order
            $packOrder->update([
                'status' => 'packed',
                'packed_at' => now(),
                'packed_by' => auth()->id(),
                'actual_weight' => $request->actual_weight,
                'actual_dimensions' => $request->actual_dimensions,
                'carton_used' => $request->carton_used,
                'packing_materials' => $request->packing_materials ?? [],
                'quality_notes' => $request->quality_notes,
                'completion_notes' => $request->completion_notes,
                'actual_pack_time' => $packOrder->started_at ? now()->diffInMinutes($packOrder->started_at) : null,
                'packing_metrics' => $packingMetrics
            ]);

            // Update packing station status
            $packOrder->packingStation->update(['status' => 'idle']);

            // Update sales order status
            $this->updateSalesOrderStatusAfterPacking($packOrder);

            // Generate shipping label if configured
            if ($this->shouldGenerateShippingLabel($packOrder)) {
                $this->generateShippingLabel($packOrder);
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.pack_order.completed', [
                'pack_order_id' => $packOrder->id,
                'pack_order_number' => $packOrder->pack_order_number,
                'sales_order_id' => $packOrder->sales_order_id,
                'packed_by' => auth()->id(),
                'actual_pack_time' => $packOrder->actual_pack_time,
                'packing_metrics' => $packingMetrics
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $packOrder->load(['salesOrder.customer', 'packingStation', 'packer', 'cartonType']),
                'message' => 'Packing completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete packing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pack order analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $packingStationId = $request->get('packing_station_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = PackOrder::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->whereHas('salesOrder', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }

        if ($packingStationId) {
            $query->where('packing_station_id', $packingStationId);
        }

        $analytics = [
            'total_pack_orders' => $query->count(),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_pack_method' => $query->groupBy('pack_method')->selectRaw('pack_method, count(*) as count')->pluck('count', 'pack_method'),
            'by_packing_station' => $this->getPackOrdersByStation($query),
            'average_pack_time' => $query->whereNotNull('actual_pack_time')->avg('actual_pack_time'),
            'packer_performance' => $this->getPackerPerformance($query),
            'efficiency_metrics' => $this->getPackingEfficiencyMetrics($query),
            'quality_metrics' => $this->getQualityMetrics($query),
            'packing_trends' => $this->getPackingTrends($dateFrom, $dateTo, $warehouseId)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Pack order analytics retrieved successfully'
        ]);
    }

    /**
     * Helper methods
     */
    private function generatePackOrderNumber(): string
    {
        $year = date('Y');
        $sequence = PackOrder::whereYear('created_at', $year)->count() + 1;
        
        return 'PO-' . $year . '-' . str_pad($sequence, 8, '0', STR_PAD_LEFT);
    }

    private function calculatePackOrderMetrics($items, $salesOrder): array
    {
        $totalWeight = 0;
        $totalVolume = 0;

        foreach ($items as $itemData) {
            $salesOrderItem = $salesOrder->items->find($itemData['sales_order_item_id']);
            $quantity = $itemData['quantity_to_pack'];
            
            $totalWeight += ($salesOrderItem->weight ?? 0) * $quantity;
            
            if ($salesOrderItem->dimensions) {
                $itemVolume = ($salesOrderItem->dimensions['length'] ?? 0) * 
                             ($salesOrderItem->dimensions['width'] ?? 0) * 
                             ($salesOrderItem->dimensions['height'] ?? 0);
                $totalVolume += $itemVolume * $quantity;
            }
        }

        return [
            'estimated_weight' => $totalWeight,
            'estimated_volume' => $totalVolume
        ];
    }

    private function determineOptimalCarton($metrics): ?int
    {
        // Find the smallest carton that can fit the items
        $carton = CartonType::where('is_active', true)
            ->where('max_weight', '>=', $metrics['estimated_weight'])
            ->where('internal_volume', '>=', $metrics['estimated_volume'])
            ->orderBy('internal_volume', 'asc')
            ->first();

        return $carton ? $carton->id : null;
    }

    private function calculatePriorityScore($salesOrder, $priorityLevel): int
    {
        $baseScores = [
            'low' => 20,
            'medium' => 40,
            'high' => 60,
            'urgent' => 80,
            'rush' => 100
        ];

        $score = $baseScores[$priorityLevel] ?? 40;

        // Add order priority bonus
        $score += ($salesOrder->priority_score ?? 0) * 0.3;

        // Add customer priority bonus
        if ($salesOrder->customer && $salesOrder->customer->priority_level === 'premium') {
            $score += 10;
        }

        return min(100, max(0, $score));
    }

    private function estimatePackTime($metrics, $packMethod): int
    {
        $baseTime = [
            'single_item' => 3,
            'multi_item' => 5,
            'fragile' => 8,
            'hazmat' => 12,
            'custom' => 15
        ];

        $methodTime = $baseTime[$packMethod] ?? 5;
        $weightTime = $metrics['estimated_weight'] * 0.1; // 6 seconds per kg
        $volumeTime = $metrics['estimated_volume'] * 0.001; // Volume factor

        return (int) ($methodTime + $weightTime + $volumeTime);
    }

    private function generatePackagingRequirements($data, $metrics): array
    {
        $requirements = [
            'pack_method' => $data['pack_method'],
            'estimated_weight' => $metrics['estimated_weight'],
            'estimated_volume' => $metrics['estimated_volume'],
            'special_handling' => []
        ];

        if ($data['fragile_items']) {
            $requirements['special_handling'][] = 'fragile_packaging';
        }

        if ($data['hazmat_items']) {
            $requirements['special_handling'][] = 'hazmat_compliance';
        }

        if ($data['requires_gift_wrap']) {
            $requirements['special_handling'][] = 'gift_wrapping';
        }

        if ($data['custom_packaging']) {
            $requirements['custom_packaging'] = $data['custom_packaging'];
        }

        return $requirements;
    }

    private function updateSalesOrderStatus($salesOrder, $packOrder): void
    {
        // Check if this is the only pack order for this sales order
        $existingPackOrders = PackOrder::where('sales_order_id', $salesOrder->id)
            ->where('id', '!=', $packOrder->id)
            ->count();

        if ($existingPackOrders === 0) {
            $salesOrder->update(['status' => 'packing']);
        } else {
            $salesOrder->update(['status' => 'partially_packed']);
        }
    }

    private function generatePackingInstructions($packOrder): array
    {
        $instructions = [
            'pack_method' => $packOrder->pack_method,
            'carton_type' => $packOrder->cartonType ? $packOrder->cartonType->name : 'TBD',
            'special_instructions' => $packOrder->special_instructions,
            'item_instructions' => []
        ];

        foreach ($packOrder->items as $item) {
            $itemInstructions = [
                'product_name' => $item->product->name,
                'quantity' => $item->quantity_to_pack,
                'special_handling' => $item->special_handling
            ];

            if ($item->fragile) {
                $itemInstructions['handling_notes'][] = 'Handle with care - fragile item';
            }

            if ($item->hazmat) {
                $itemInstructions['handling_notes'][] = 'Hazmat item - follow safety protocols';
            }

            $instructions['item_instructions'][] = $itemInstructions;
        }

        if ($packOrder->requires_gift_wrap) {
            $instructions['post_pack_steps'][] = 'Apply gift wrapping';
        }

        return $instructions;
    }

    private function calculateProgress($packOrder): array
    {
        $totalItems = $packOrder->items()->count();
        $completedItems = $packOrder->items()->where('status', 'completed')->count();
        $partialItems = $packOrder->items()->where('status', 'partial')->count();

        return [
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'partial_items' => $partialItems,
            'pending_items' => $totalItems - $completedItems - $partialItems,
            'completion_percentage' => $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0,
            'total_quantity_to_pack' => $packOrder->items()->sum('quantity_to_pack'),
            'total_quantity_packed' => $packOrder->items()->sum('quantity_packed')
        ];
    }

    private function handlePackerAssignment($packOrder, $oldPacker, $newPacker): void
    {
        if ($newPacker && $packOrder->status === 'created') {
            $packOrder->update(['status' => 'assigned']);
        }

        // Fire packer assignment event
        $this->fireTransactionalEvent('outbound.pack_order.packer_assigned', [
            'pack_order_id' => $packOrder->id,
            'pack_order_number' => $packOrder->pack_order_number,
            'old_packer_id' => $oldPacker,
            'new_packer_id' => $newPacker,
            'assigned_by' => auth()->id()
        ]);
    }

    private function handleStatusChange($packOrder, $oldStatus, $newStatus): void
    {
        // Handle status-specific logic
        $statusTimestamps = [
            'assigned' => ['assigned_at' => now()],
            'in_progress' => ['started_at' => now()],
            'packed' => ['packed_at' => now()],
            'quality_check' => ['quality_check_at' => now()],
            'completed' => ['completed_at' => now()],
            'cancelled' => ['cancelled_at' => now()]
        ];

        if (isset($statusTimestamps[$newStatus])) {
            $packOrder->update($statusTimestamps[$newStatus]);
        }

        // Update packing station status
        if ($newStatus === 'cancelled' || $newStatus === 'completed') {
            $packOrder->packingStation->update(['status' => 'idle']);
        }

        // Fire status change event
        $this->fireTransactionalEvent('outbound.pack_order.status_changed', [
            'pack_order_id' => $packOrder->id,
            'pack_order_number' => $packOrder->pack_order_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);
    }

    private function calculatePackingMetrics($packOrder, $completionData): array
    {
        $estimatedWeight = $packOrder->estimated_weight;
        $actualWeight = $completionData['actual_weight'];
        
        $estimatedVolume = $packOrder->estimated_volume;
        $actualVolume = $completionData['actual_dimensions']['length'] * 
                       $completionData['actual_dimensions']['width'] * 
                       $completionData['actual_dimensions']['height'];

        return [
            'weight_accuracy' => $estimatedWeight > 0 ? (1 - abs($actualWeight - $estimatedWeight) / $estimatedWeight) * 100 : 100,
            'volume_efficiency' => $actualVolume > 0 ? ($estimatedVolume / $actualVolume) * 100 : 0,
            'pack_time_efficiency' => $packOrder->estimated_pack_time > 0 && $packOrder->actual_pack_time > 0 
                ? ($packOrder->estimated_pack_time / $packOrder->actual_pack_time) * 100 
                : 0,
            'items_packed_successfully' => $packOrder->items()->where('status', 'completed')->count(),
            'items_with_issues' => $packOrder->items()->where('condition', '!=', 'good')->count()
        ];
    }

    private function updateSalesOrderStatusAfterPacking($packOrder): void
    {
        $salesOrder = $packOrder->salesOrder;
        
        // Check if all pack orders for this sales order are completed
        $allPackOrdersCompleted = PackOrder::where('sales_order_id', $salesOrder->id)
            ->whereNotIn('status', ['packed', 'quality_check', 'completed'])
            ->count() === 0;

        if ($allPackOrdersCompleted) {
            $salesOrder->update(['status' => 'packed']);
        }
    }

    private function shouldGenerateShippingLabel($packOrder): bool
    {
        // Check if automatic shipping label generation is enabled
        return $packOrder->salesOrder->auto_generate_label ?? false;
    }

    private function generateShippingLabel($packOrder): void
    {
        // Integration with shipping label generation system
        // This would create a shipping label and update the pack order
    }

    /**
     * Analytics helper methods
     */
    private function getPackOrdersByStation($query): array
    {
        return $query->with('packingStation')
            ->selectRaw('packing_station_id, count(*) as pack_count, avg(actual_pack_time) as avg_time')
            ->groupBy('packing_station_id')
            ->get()
            ->map(function ($item) {
                return [
                    'packing_station' => $item->packingStation,
                    'pack_count' => $item->pack_count,
                    'avg_time' => $item->avg_time
                ];
            })
            ->toArray();
    }

    private function getPackerPerformance($query): array
    {
        return $query->with('packer')
            ->whereNotNull('packer_id')
            ->whereNotNull('actual_pack_time')
            ->selectRaw('packer_id, count(*) as pack_count, avg(actual_pack_time) as avg_time, avg(priority_score) as avg_priority')
            ->groupBy('packer_id')
            ->get()
            ->map(function ($item) {
                return [
                    'packer' => $item->packer,
                    'pack_count' => $item->pack_count,
                    'avg_time' => $item->avg_time,
                    'avg_priority' => $item->avg_priority
                ];
            })
            ->toArray();
    }

    private function getPackingEfficiencyMetrics($query): array
    {
        $completed = $query->where('status', 'completed');
        
        return [
            'completion_rate' => $query->count() > 0 ? ($completed->count() / $query->count()) * 100 : 0,
            'average_time_vs_estimate' => $this->calculateTimeAccuracy($completed),
            'weight_accuracy' => $this->calculateWeightAccuracy($completed),
            'volume_efficiency' => $this->calculateVolumeEfficiency($completed)
        ];
    }

    private function calculateTimeAccuracy($query): float
    {
        $items = $query->whereNotNull('actual_pack_time')->get();
        $accurateCount = 0;
        
        foreach ($items as $item) {
            $variance = abs($item->actual_pack_time - $item->estimated_pack_time);
            $tolerance = $item->estimated_pack_time * 0.2; // 20% tolerance
            
            if ($variance <= $tolerance) {
                $accurateCount++;
            }
        }
        
        return $items->count() > 0 ? ($accurateCount / $items->count()) * 100 : 0;
    }

    private function calculateWeightAccuracy($query): float
    {
        $items = $query->whereNotNull('actual_weight')->get();
        $totalAccuracy = 0;
        
        foreach ($items as $item) {
            if ($item->estimated_weight > 0) {
                $accuracy = 1 - abs($item->actual_weight - $item->estimated_weight) / $item->estimated_weight;
                $totalAccuracy += max(0, $accuracy);
            }
        }
        
        return $items->count() > 0 ? ($totalAccuracy / $items->count()) * 100 : 0;
    }

    private function calculateVolumeEfficiency($query): float
    {
        $items = $query->whereNotNull('actual_dimensions')->get();
        $totalEfficiency = 0;
        
        foreach ($items as $item) {
            $actualVolume = $item->actual_dimensions['length'] * 
                           $item->actual_dimensions['width'] * 
                           $item->actual_dimensions['height'];
            
            if ($actualVolume > 0 && $item->estimated_volume > 0) {
                $efficiency = ($item->estimated_volume / $actualVolume) * 100;
                $totalEfficiency += min(100, $efficiency);
            }
        }
        
        return $items->count() > 0 ? $totalEfficiency / $items->count() : 0;
    }

    private function getQualityMetrics($query): array
    {
        $completed = $query->where('status', 'completed');
        $totalItems = PackOrderItem::whereIn('pack_order_id', $completed->pluck('id'));
        
        return [
            'items_packed_successfully' => $totalItems->where('status', 'completed')->count(),
            'items_with_damage' => $totalItems->where('condition', 'damaged')->count(),
            'items_missing' => $totalItems->where('condition', 'missing')->count(),
            'quality_score' => $this->calculateOverallQualityScore($totalItems),
            'rework_rate' => $this->calculateReworkRate($completed)
        ];
    }

    private function calculateOverallQualityScore($itemsQuery): float
    {
        $total = $itemsQuery->count();
        $good = $itemsQuery->where('condition', 'good')->count();
        
        return $total > 0 ? ($good / $total) * 100 : 0;
    }

    private function calculateReworkRate($query): float
    {
        // Calculate rework rate based on quality issues
        $total = $query->count();
        $rework = $query->whereNotNull('quality_notes')->count();
        
        return $total > 0 ? ($rework / $total) * 100 : 0;
    }

    private function getPackingTrends($dateFrom, $dateTo, $warehouseId): array
    {
        $query = PackOrder::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, count(*) as pack_count, avg(actual_pack_time) as avg_time')
            ->groupBy('date')
            ->orderBy('date');

        if ($warehouseId) {
            $query->whereHas('salesOrder', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }

        return $query->get()->toArray();
    }
}