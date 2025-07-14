<?php

namespace App\Http\Controllers\ECommerce;

use App\Http\Controllers\Controller;
use App\Models\ECommerce\OrderFulfillment;
use App\Models\ECommerce\OrderFulfillmentItem;
use App\Services\ECommerce\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderFulfillmentController extends Controller
{
    protected $fulfillmentService;

    public function __construct(OrderFulfillmentService $fulfillmentService)
    {
        $this->fulfillmentService = $fulfillmentService;
    }

    /**
     * Display a listing of order fulfillments
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrderFulfillment::with(['salesOrder', 'shippingCarrier', 'fulfillmentItems']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('fulfillment_status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority_level', $request->priority);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $fulfillments = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $fulfillments
        ]);
    }

    /**
     * Store a newly created order fulfillment
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'fulfillment_type' => 'required|string',
            'priority_level' => 'required|in:low,medium,high,urgent',
            'shipping_carrier_id' => 'nullable|exists:shipping_carriers,id',
            'automation_rules' => 'nullable|array',
            'items' => 'required|array',
            'items.*.sales_order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0'
        ]);

        try {
            $fulfillment = $this->fulfillmentService->createFulfillment($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Order fulfillment created successfully',
                'data' => $fulfillment->load(['salesOrder', 'fulfillmentItems'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order fulfillment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order fulfillment
     */
    public function show($id): JsonResponse
    {
        $fulfillment = OrderFulfillment::with([
            'salesOrder',
            'shippingCarrier',
            'fulfillmentItems.product',
            'fulfillmentHistory.changedBy'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $fulfillment
        ]);
    }

    /**
     * Update the specified order fulfillment
     */
    public function update(Request $request, $id): JsonResponse
    {
        $fulfillment = OrderFulfillment::findOrFail($id);

        $request->validate([
            'fulfillment_status' => 'sometimes|string',
            'priority_level' => 'sometimes|in:low,medium,high,urgent',
            'estimated_ship_date' => 'sometimes|date',
            'shipping_carrier_id' => 'sometimes|exists:shipping_carriers,id',
            'automation_rules' => 'sometimes|array',
            'fulfillment_notes' => 'sometimes|string'
        ]);

        try {
            $fulfillment->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Order fulfillment updated successfully',
                'data' => $fulfillment->fresh(['salesOrder', 'fulfillmentItems'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order fulfillment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process automated fulfillment
     */
    public function processAutomation($id): JsonResponse
    {
        try {
            $fulfillment = OrderFulfillment::findOrFail($id);
            $result = $this->fulfillmentService->processAutomatedFulfillment($fulfillment);

            return response()->json([
                'success' => true,
                'message' => 'Automated fulfillment processed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process automated fulfillment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update fulfillment status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        try {
            $fulfillment = OrderFulfillment::findOrFail($id);
            $fulfillment->updateStatus($request->status, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Fulfillment status updated successfully',
                'data' => $fulfillment->fresh(['fulfillmentHistory'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fulfillment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fulfillment analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $analytics = $this->fulfillmentService->getFulfillmentAnalytics($request->all());

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get fulfillment analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending fulfillments for automation
     */
    public function pendingAutomation(): JsonResponse
    {
        $pendingFulfillments = OrderFulfillment::with(['salesOrder', 'fulfillmentItems'])
            ->where('fulfillment_status', 'pending')
            ->whereNotNull('automation_rules')
            ->get()
            ->filter(function ($fulfillment) {
                return $fulfillment->canAutomate();
            });

        return response()->json([
            'success' => true,
            'data' => $pendingFulfillments
        ]);
    }
}