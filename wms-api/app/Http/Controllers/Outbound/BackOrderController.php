<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\BackOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\ProductInventory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class BackOrderController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of back orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = BackOrder::with([
            'salesOrder.customer',
            'salesOrderItem.product',
            'warehouse'
        ]);

        // Apply filters
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority_level')) {
            $query->where('priority_level', $request->priority_level);
        }

        if ($request->has('customer_id')) {
            $query->whereHas('salesOrder', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        if ($request->has('product_id')) {
            $query->whereHas('salesOrderItem', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $backOrders = $query->orderBy('priority_level', 'desc')
            ->orderBy('created_at', 'asc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $backOrders,
            'message' => 'Back orders retrieved successfully'
        ]);
    }

    /**
     * Store a newly created back order
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sales_order_id' => 'required|exists:sales_orders,id',
            'sales_order_item_id' => 'required|exists:sales_order_items,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'backordered_quantity' => 'required|integer|min:1',
            'priority_level' => 'required|in:low,medium,high,urgent,critical',
            'reason' => 'required|string',
            'expected_availability_date' => 'nullable|date|after:today',
            'customer_notification_sent' => 'boolean',
            'auto_fulfill' => 'boolean',
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

            // Generate back order number
            $backOrderNumber = $this->generateBackOrderNumber();

            // Validate sales order item
            $salesOrderItem = SalesOrderItem::with('salesOrder')->find($request->sales_order_item_id);
            
            if ($salesOrderItem->sales_order_id !== $request->sales_order_id) {
                throw new \Exception('Sales order item does not belong to the specified sales order');
            }

            $backOrder = BackOrder::create([
                'backorder_number' => $backOrderNumber,
                'sales_order_id' => $request->sales_order_id,
                'sales_order_item_id' => $request->sales_order_item_id,
                'warehouse_id' => $request->warehouse_id,
                'backordered_quantity' => $request->backordered_quantity,
                'fulfilled_quantity' => 0,
                'remaining_quantity' => $request->backordered_quantity,
                'priority_level' => $request->priority_level,
                'status' => 'pending',
                'reason' => $request->reason,
                'expected_availability_date' => $request->expected_availability_date,
                'customer_notification_sent' => $request->customer_notification_sent ?? false,
                'auto_fulfill' => $request->auto_fulfill ?? true,
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            // Update sales order item status
            $salesOrderItem->update([
                'backorder_quantity' => ($salesOrderItem->backorder_quantity ?? 0) + $request->backordered_quantity,
                'status' => 'backordered'
            ]);

            // Create inventory reservation if auto-fulfill is enabled
            if ($request->auto_fulfill) {
                $this->createInventoryReservation($backOrder);
            }

            // Send customer notification if requested
            if ($request->customer_notification_sent) {
                $this->sendCustomerNotification($backOrder);
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.backorder.created', [
                'backorder_id' => $backOrder->id,
                'backorder_number' => $backOrderNumber,
                'sales_order_id' => $request->sales_order_id,
                'product_id' => $salesOrderItem->product_id,
                'backordered_quantity' => $request->backordered_quantity,
                'warehouse_id' => $request->warehouse_id,
                'priority_level' => $request->priority_level
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $backOrder->load(['salesOrder.customer', 'salesOrderItem.product', 'warehouse']),
                'message' => 'Back order created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create back order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified back order
     */
    public function show($id): JsonResponse
    {
        $backOrder = BackOrder::with([
            'salesOrder.customer',
            'salesOrderItem.product',
            'warehouse',
            'createdBy',
            'fulfillmentHistory'
        ])->find($id);

        if (!$backOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Back order not found'
            ], 404);
        }

        // Get current inventory availability
        $currentInventory = $this->getCurrentInventoryAvailability($backOrder);

        return response()->json([
            'success' => true,
            'data' => array_merge($backOrder->toArray(), [
                'current_inventory' => $currentInventory
            ]),
            'message' => 'Back order retrieved successfully'
        ]);
    }

    /**
     * Update the specified back order
     */
    public function update(Request $request, $id): JsonResponse
    {
        $backOrder = BackOrder::find($id);

        if (!$backOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Back order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'priority_level' => 'sometimes|in:low,medium,high,urgent,critical',
            'expected_availability_date' => 'nullable|date',
            'auto_fulfill' => 'sometimes|boolean',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,processing,partially_fulfilled,fulfilled,cancelled'
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

            $oldStatus = $backOrder->status;
            $oldPriority = $backOrder->priority_level;

            $backOrder->update($request->only([
                'priority_level',
                'expected_availability_date',
                'auto_fulfill',
                'notes',
                'status'
            ]));

            // Handle status changes
            if ($request->has('status') && $oldStatus !== $request->status) {
                $this->handleStatusChange($backOrder, $oldStatus, $request->status);
            }

            // Handle priority changes
            if ($request->has('priority_level') && $oldPriority !== $request->priority_level) {
                $this->handlePriorityChange($backOrder, $oldPriority, $request->priority_level);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $backOrder->load(['salesOrder.customer', 'salesOrderItem.product', 'warehouse']),
                'message' => 'Back order updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update back order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fulfill back order (partial or complete)
     */
    public function fulfill(Request $request, $id): JsonResponse
    {
        $backOrder = BackOrder::find($id);

        if (!$backOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Back order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'fulfill_quantity' => 'required|integer|min:1|max:' . $backOrder->remaining_quantity,
            'location_id' => 'required|exists:locations,id',
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

            // Check inventory availability
            $availableInventory = $this->checkInventoryAvailability(
                $backOrder->salesOrderItem->product_id,
                $backOrder->warehouse_id,
                $request->location_id,
                $request->fulfill_quantity
            );

            if (!$availableInventory) {
                throw new \Exception('Insufficient inventory available for fulfillment');
            }

            // Update back order quantities
            $backOrder->fulfilled_quantity += $request->fulfill_quantity;
            $backOrder->remaining_quantity -= $request->fulfill_quantity;

            // Update status based on remaining quantity
            if ($backOrder->remaining_quantity === 0) {
                $backOrder->status = 'fulfilled';
                $backOrder->fulfilled_date = now();
            } else {
                $backOrder->status = 'partially_fulfilled';
            }

            $backOrder->save();

            // Create fulfillment record
            $this->createFulfillmentRecord($backOrder, $request->fulfill_quantity, $request->location_id, $request->notes);

            // Update sales order item
            $this->updateSalesOrderItem($backOrder, $request->fulfill_quantity);

            // Reserve/allocate inventory
            $this->allocateInventory(
                $backOrder->salesOrderItem->product_id,
                $backOrder->warehouse_id,
                $request->location_id,
                $request->fulfill_quantity
            );

            // Fire event
            $this->fireTransactionalEvent('outbound.backorder.fulfilled', [
                'backorder_id' => $backOrder->id,
                'backorder_number' => $backOrder->backorder_number,
                'fulfill_quantity' => $request->fulfill_quantity,
                'remaining_quantity' => $backOrder->remaining_quantity,
                'is_complete' => $backOrder->remaining_quantity === 0,
                'fulfilled_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $backOrder->load(['salesOrder.customer', 'salesOrderItem.product', 'warehouse']),
                'message' => $backOrder->remaining_quantity === 0 
                    ? 'Back order fulfilled completely' 
                    : 'Back order partially fulfilled'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to fulfill back order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel back order
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $backOrder = BackOrder::find($id);

        if (!$backOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Back order not found'
            ], 404);
        }

        if ($backOrder->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Back order is already cancelled'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string',
            'notify_customer' => 'boolean'
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

            $backOrder->update([
                'status' => 'cancelled',
                'cancelled_date' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $request->cancellation_reason
            ]);

            // Update sales order item
            $salesOrderItem = $backOrder->salesOrderItem;
            $salesOrderItem->backorder_quantity = max(0, 
                ($salesOrderItem->backorder_quantity ?? 0) - $backOrder->remaining_quantity
            );
            
            if ($salesOrderItem->backorder_quantity === 0) {
                $salesOrderItem->status = 'pending'; // Reset to pending for reprocessing
            }
            
            $salesOrderItem->save();

            // Remove any inventory reservations
            $this->removeInventoryReservations($backOrder);

            // Send customer notification if requested
            if ($request->notify_customer) {
                $this->sendCancellationNotification($backOrder, $request->cancellation_reason);
            }

            // Fire event
            $this->fireTransactionalEvent('outbound.backorder.cancelled', [
                'backorder_id' => $backOrder->id,
                'backorder_number' => $backOrder->backorder_number,
                'cancelled_quantity' => $backOrder->remaining_quantity,
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $backOrder->load(['salesOrder.customer', 'salesOrderItem.product', 'warehouse']),
                'message' => 'Back order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel back order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get back order analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = BackOrder::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $analytics = [
            'total_backorders' => $query->count(),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_priority' => $query->groupBy('priority_level')->selectRaw('priority_level, count(*) as count')->pluck('count', 'priority_level'),
            'total_backordered_quantity' => $query->sum('backordered_quantity'),
            'total_fulfilled_quantity' => $query->sum('fulfilled_quantity'),
            'fulfillment_rate' => $this->calculateFulfillmentRate($query),
            'average_fulfillment_time' => $this->getAverageFulfillmentTime($query),
            'top_backordered_products' => $this->getTopBackorderedProducts($query),
            'backorder_trends' => $this->getBackorderTrends($dateFrom, $dateTo, $warehouseId)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Back order analytics retrieved successfully'
        ]);
    }

    /**
     * Generate back order number
     */
    private function generateBackOrderNumber(): string
    {
        $year = date('Y');
        $sequence = BackOrder::whereYear('created_at', $year)->count() + 1;
        
        return 'BO-' . $year . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Handle status changes
     */
    private function handleStatusChange($backOrder, $oldStatus, $newStatus): void
    {
        // Fire status change event
        $this->fireTransactionalEvent('outbound.backorder.status_changed', [
            'backorder_id' => $backOrder->id,
            'backorder_number' => $backOrder->backorder_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);
    }

    /**
     * Handle priority changes
     */
    private function handlePriorityChange($backOrder, $oldPriority, $newPriority): void
    {
        // Fire priority change event
        $this->fireTransactionalEvent('outbound.backorder.priority_changed', [
            'backorder_id' => $backOrder->id,
            'backorder_number' => $backOrder->backorder_number,
            'old_priority' => $oldPriority,
            'new_priority' => $newPriority,
            'changed_by' => auth()->id()
        ]);
    }

    /**
     * Helper methods for inventory and fulfillment operations
     */
    private function getCurrentInventoryAvailability($backOrder): array
    {
        $inventory = ProductInventory::where('product_id', $backOrder->salesOrderItem->product_id)
            ->where('warehouse_id', $backOrder->warehouse_id)
            ->first();

        return [
            'available_quantity' => $inventory->quantity_available ?? 0,
            'on_hand_quantity' => $inventory->quantity_on_hand ?? 0,
            'reserved_quantity' => $inventory->quantity_reserved ?? 0
        ];
    }

    private function checkInventoryAvailability($productId, $warehouseId, $locationId, $quantity): bool
    {
        $inventory = ProductInventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('location_id', $locationId)
            ->first();

        return $inventory && $inventory->quantity_available >= $quantity;
    }

    private function createInventoryReservation($backOrder): void
    {
        // Implementation for creating inventory reservation
        // This would integrate with your inventory reservation system
    }

    private function createFulfillmentRecord($backOrder, $quantity, $locationId, $notes): void
    {
        // Implementation for creating fulfillment history record
        // This would create a record in a backorder_fulfillments table
    }

    private function updateSalesOrderItem($backOrder, $fulfilledQuantity): void
    {
        $salesOrderItem = $backOrder->salesOrderItem;
        $salesOrderItem->backorder_quantity = max(0, 
            ($salesOrderItem->backorder_quantity ?? 0) - $fulfilledQuantity
        );
        
        if ($salesOrderItem->backorder_quantity === 0) {
            $salesOrderItem->status = 'allocated'; // Ready for picking
        }
        
        $salesOrderItem->save();
    }

    private function allocateInventory($productId, $warehouseId, $locationId, $quantity): void
    {
        // Implementation for inventory allocation
        // This would update inventory quantities and create allocation records
    }

    private function removeInventoryReservations($backOrder): void
    {
        // Implementation for removing inventory reservations
        // This would clean up any reserved inventory for the cancelled backorder
    }

    private function sendCustomerNotification($backOrder): void
    {
        // Implementation for sending customer notification
        // This would integrate with your notification system
    }

    private function sendCancellationNotification($backOrder, $reason): void
    {
        // Implementation for sending cancellation notification
        // This would notify the customer about the cancellation
    }

    private function calculateFulfillmentRate($query): float
    {
        $total = $query->sum('backordered_quantity');
        $fulfilled = $query->sum('fulfilled_quantity');
        
        return $total > 0 ? ($fulfilled / $total) * 100 : 0;
    }

    private function getAverageFulfillmentTime($query): float
    {
        return $query->whereNotNull('fulfilled_date')
            ->selectRaw('AVG(DATEDIFF(fulfilled_date, created_at)) as avg_days')
            ->value('avg_days') ?? 0;
    }

    private function getTopBackorderedProducts($query): array
    {
        return $query->with('salesOrderItem.product')
            ->selectRaw('sales_order_item_id, sum(backordered_quantity) as total_quantity')
            ->groupBy('sales_order_item_id')
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'product' => $item->salesOrderItem->product,
                    'total_backordered' => $item->total_quantity
                ];
            })
            ->toArray();
    }

    private function getBackorderTrends($dateFrom, $dateTo, $warehouseId): array
    {
        $query = BackOrder::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, count(*) as count, sum(backordered_quantity) as quantity')
            ->groupBy('date')
            ->orderBy('date');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->toArray();
    }
}