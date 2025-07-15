<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\OrderAllocation;
use App\Models\SalesOrder;
use App\Models\ProductInventory;
use App\Services\Outbound\OrderAllocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderAllocationController extends Controller
{
    protected $allocationService;

    public function __construct(OrderAllocationService $allocationService)
    {
        $this->allocationService = $allocationService;
    }

    /**
     * Get all allocations with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrderAllocation::with([
            'salesOrder',
            'salesOrderItem',
            'product',
            'location',
            'allocatedBy'
        ]);

        // Apply filters
        if ($request->has('status')) {
            $query->where('allocation_status', $request->status);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('sales_order_id')) {
            $query->where('sales_order_id', $request->sales_order_id);
        }

        if ($request->has('expired')) {
            if ($request->expired === 'true') {
                $query->expired();
            } else {
                $query->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>=', now());
                });
            }
        }

        $allocations = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $allocations,
            'message' => 'Allocations retrieved successfully'
        ]);
    }

    /**
     * Allocate inventory for a sales order
     */
    public function allocateOrder(Request $request, $orderId): JsonResponse
    {
        $request->validate([
            'allocation_type' => 'required|in:fifo,lifo,fefo,manual',
            'allocation_rules' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            $salesOrder = SalesOrder::findOrFail($orderId);
            
            $allocations = $this->allocationService->allocateOrder(
                $salesOrder,
                $request->allocation_type,
                $request->allocation_rules,
                $request->expires_at
            );

            return response()->json([
                'success' => true,
                'data' => $allocations,
                'message' => 'Order allocated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Allocation failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Allocate specific item
     */
    public function allocateItem(Request $request): JsonResponse
    {
        $request->validate([
            'sales_order_item_id' => 'required|exists:sales_order_items,id',
            'location_id' => 'required|exists:locations,id',
            'quantity' => 'required|numeric|min:0.001',
            'allocation_type' => 'required|in:fifo,lifo,fefo,manual',
            'lot_number' => 'nullable|string',
            'serial_number' => 'nullable|string',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            $allocation = $this->allocationService->allocateItem(
                $request->sales_order_item_id,
                $request->location_id,
                $request->quantity,
                $request->allocation_type,
                $request->lot_number,
                $request->serial_number,
                $request->expires_at
            );

            return response()->json([
                'success' => true,
                'data' => $allocation,
                'message' => 'Item allocated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Allocation failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get allocation details
     */
    public function show($id): JsonResponse
    {
        $allocation = OrderAllocation::with([
            'salesOrder',
            'salesOrderItem',
            'product',
            'location',
            'allocatedBy'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $allocation,
            'message' => 'Allocation retrieved successfully'
        ]);
    }

    /**
     * Update allocation
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'allocated_quantity' => 'nullable|numeric|min:0.001',
            'expires_at' => 'nullable|date',
            'allocation_rules' => 'nullable|array',
        ]);

        try {
            $allocation = OrderAllocation::findOrFail($id);
            
            // Check if allocation can be modified
            if ($allocation->allocation_status === 'picked') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify picked allocation'
                ], 400);
            }

            $allocation->update($request->only([
                'allocated_quantity',
                'expires_at',
                'allocation_rules'
            ]));

            return response()->json([
                'success' => true,
                'data' => $allocation->fresh(),
                'message' => 'Allocation updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel allocation
     */
    public function cancel($id): JsonResponse
    {
        try {
            $allocation = OrderAllocation::findOrFail($id);
            
            if ($allocation->picked_quantity > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel allocation with picked quantity'
                ], 400);
            }

            $this->allocationService->cancelAllocation($allocation);

            return response()->json([
                'success' => true,
                'message' => 'Allocation cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reallocate expired allocations
     */
    public function reallocateExpired(): JsonResponse
    {
        try {
            $reallocated = $this->allocationService->reallocateExpired();

            return response()->json([
                'success' => true,
                'data' => [
                    'reallocated_count' => count($reallocated),
                    'allocations' => $reallocated
                ],
                'message' => 'Expired allocations reallocated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reallocation failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get allocation summary for order
     */
    public function orderSummary($orderId): JsonResponse
    {
        $summary = $this->allocationService->getOrderAllocationSummary($orderId);

        return response()->json([
            'success' => true,
            'data' => $summary,
            'message' => 'Allocation summary retrieved successfully'
        ]);
    }

    /**
     * Get available inventory for allocation
     */
    public function availableInventory(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity_needed' => 'required|numeric|min:0.001',
            'allocation_type' => 'required|in:fifo,lifo,fefo,manual',
        ]);

        $availableInventory = $this->allocationService->getAvailableInventory(
            $request->product_id,
            $request->quantity_needed,
            $request->allocation_type
        );

        return response()->json([
            'success' => true,
            'data' => $availableInventory,
            'message' => 'Available inventory retrieved successfully'
        ]);
    }

    /**
     * Bulk allocate multiple orders
     */
    public function bulkAllocate(Request $request): JsonResponse
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:sales_orders,id',
            'allocation_type' => 'required|in:fifo,lifo,fefo,manual',
            'allocation_rules' => 'nullable|array',
        ]);

        try {
            $results = $this->allocationService->bulkAllocateOrders(
                $request->order_ids,
                $request->allocation_type,
                $request->allocation_rules
            );

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Bulk allocation completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk allocation failed: ' . $e->getMessage()
            ], 400);
        }
    }
}