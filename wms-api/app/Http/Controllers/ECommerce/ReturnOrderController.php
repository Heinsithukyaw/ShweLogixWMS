<?php

namespace App\Http\Controllers\ECommerce;

use App\Http\Controllers\Controller;
use App\Models\ECommerce\ReturnOrder;
use App\Models\ECommerce\ReturnOrderItem;
use App\Services\ECommerce\ReturnOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReturnOrderController extends Controller
{
    protected $returnService;

    public function __construct(ReturnOrderService $returnService)
    {
        $this->returnService = $returnService;
    }

    /**
     * Display a listing of return orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = ReturnOrder::with(['originalOrder', 'customer', 'returnItems']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('return_status', $request->status);
        }

        if ($request->has('return_type')) {
            $query->where('return_type', $request->return_type);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('requested_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('requested_date', '<=', $request->date_to);
        }

        $returns = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $returns
        ]);
    }

    /**
     * Store a newly created return order
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'original_order_id' => 'required|exists:sales_orders,id',
            'customer_id' => 'required|exists:business_parties,id',
            'return_reason' => 'required|string',
            'return_type' => 'required|in:refund,exchange,store_credit',
            'items' => 'required|array',
            'items.*.original_order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.quantity_returned' => 'required|numeric|min:0',
            'items.*.return_reason' => 'required|string'
        ]);

        try {
            $returnOrder = $this->returnService->createReturnOrder($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Return order created successfully',
                'data' => $returnOrder->load(['originalOrder', 'customer', 'returnItems'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create return order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified return order
     */
    public function show($id): JsonResponse
    {
        $returnOrder = ReturnOrder::with([
            'originalOrder',
            'customer',
            'returnItems.product',
            'returnHistory.changedBy',
            'approvedBy',
            'processedBy'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $returnOrder
        ]);
    }

    /**
     * Update the specified return order
     */
    public function update(Request $request, $id): JsonResponse
    {
        $returnOrder = ReturnOrder::findOrFail($id);

        $request->validate([
            'return_reason' => 'sometimes|string',
            'return_type' => 'sometimes|in:refund,exchange,store_credit',
            'restocking_fee' => 'sometimes|numeric|min:0',
            'return_shipping_cost' => 'sometimes|numeric|min:0',
            'inspection_notes' => 'sometimes|string',
            'processing_notes' => 'sometimes|string'
        ]);

        try {
            $returnOrder->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Return order updated successfully',
                'data' => $returnOrder->fresh(['originalOrder', 'customer', 'returnItems'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update return order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve return order
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        try {
            $returnOrder = ReturnOrder::findOrFail($id);
            $returnOrder->approve(auth()->id(), $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Return order approved successfully',
                'data' => $returnOrder->fresh(['returnHistory'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve return order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark return order as received
     */
    public function receive(Request $request, $id): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:return_order_items,id',
            'items.*.condition_received' => 'required|in:new,like_new,good,fair,poor,damaged',
            'items.*.inspection_notes' => 'nullable|string'
        ]);

        try {
            $returnOrder = ReturnOrder::findOrFail($id);
            $result = $this->returnService->receiveReturnOrder($returnOrder, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Return order received successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive return order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process return order
     */
    public function process(Request $request, $id): JsonResponse
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:return_order_items,id',
            'items.*.disposition' => 'required|in:restock,resell,donate,dispose,return_to_vendor'
        ]);

        try {
            $returnOrder = ReturnOrder::findOrFail($id);
            $result = $this->returnService->processReturnOrder($returnOrder, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Return order processed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process return order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get return order analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $analytics = $this->returnService->getReturnAnalytics($request->all());

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get return analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate refund amount
     */
    public function calculateRefund($id): JsonResponse
    {
        try {
            $returnOrder = ReturnOrder::with('returnItems')->findOrFail($id);
            $refundAmount = $returnOrder->calculateRefundAmount();

            return response()->json([
                'success' => true,
                'data' => [
                    'refund_amount' => $refundAmount,
                    'restocking_fee' => $returnOrder->restocking_fee,
                    'return_shipping_cost' => $returnOrder->return_shipping_cost,
                    'net_refund' => $refundAmount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate refund amount',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}