<?php

namespace App\Http\Controllers\Returns;

use App\Http\Controllers\Controller;
use App\Models\Returns\ReturnAuthorization;
use App\Models\Returns\ReturnAuthorizationItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class ReturnAuthorizationController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of return authorizations
     */
    public function index(Request $request): JsonResponse
    {
        $query = ReturnAuthorization::with(['customer', 'warehouse', 'items.product']);

        // Apply filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('warehouse_id')) {
            $query->byWarehouse($request->warehouse_id);
        }

        if ($request->has('customer_id')) {
            $query->byCustomer($request->customer_id);
        }

        if ($request->has('date_from')) {
            $query->where('requested_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('requested_date', '<=', $request->date_to);
        }

        $returnAuthorizations = $query->orderBy('requested_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $returnAuthorizations,
            'message' => 'Return authorizations retrieved successfully'
        ]);
    }

    /**
     * Store a newly created return authorization
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:business_parties,id',
            'original_order_id' => 'nullable|exists:sales_orders,id',
            'return_type' => 'required|in:defective,damaged,wrong_item,customer_change,warranty,recall',
            'reason' => 'required|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'requested_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after:requested_date',
            'customer_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.requested_quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.condition_expected' => 'required|in:new,used,damaged,defective',
            'items.*.serial_number' => 'nullable|string',
            'items.*.batch_number' => 'nullable|string',
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

            // Generate RMA number
            $rmaNumber = 'RMA-' . date('Y') . '-' . str_pad(
                ReturnAuthorization::whereYear('created_at', date('Y'))->count() + 1,
                6,
                '0',
                STR_PAD_LEFT
            );

            // Calculate estimated value
            $estimatedValue = collect($request->items)->sum(function ($item) {
                return $item['requested_quantity'] * $item['unit_price'];
            });

            $returnAuthorization = ReturnAuthorization::create([
                'rma_number' => $rmaNumber,
                'customer_id' => $request->customer_id,
                'original_order_id' => $request->original_order_id,
                'return_type' => $request->return_type,
                'status' => 'pending',
                'reason' => $request->reason,
                'customer_notes' => $request->customer_notes,
                'estimated_value' => $estimatedValue,
                'requested_date' => $request->requested_date,
                'expected_return_date' => $request->expected_return_date,
                'warehouse_id' => $request->warehouse_id,
                'return_shipping_info' => $request->return_shipping_info ?? []
            ]);

            // Create return authorization items
            foreach ($request->items as $itemData) {
                ReturnAuthorizationItem::create([
                    'return_authorization_id' => $returnAuthorization->id,
                    'product_id' => $itemData['product_id'],
                    'original_order_item_id' => $itemData['original_order_item_id'] ?? null,
                    'requested_quantity' => $itemData['requested_quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_value' => $itemData['requested_quantity'] * $itemData['unit_price'],
                    'condition_expected' => $itemData['condition_expected'],
                    'item_notes' => $itemData['item_notes'] ?? null,
                    'serial_number' => $itemData['serial_number'] ?? null,
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);
            }

            // Fire event
            $this->fireTransactionalEvent('returns.authorization.created', [
                'return_authorization_id' => $returnAuthorization->id,
                'rma_number' => $rmaNumber,
                'customer_id' => $request->customer_id,
                'warehouse_id' => $request->warehouse_id,
                'estimated_value' => $estimatedValue,
                'items_count' => count($request->items)
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $returnAuthorization->load(['customer', 'warehouse', 'items.product']),
                'message' => 'Return authorization created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create return authorization: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified return authorization
     */
    public function show($id): JsonResponse
    {
        $returnAuthorization = ReturnAuthorization::with([
            'customer',
            'originalOrder',
            'warehouse',
            'approvedBy',
            'processedBy',
            'items.product',
            'receipts.items'
        ])->find($id);

        if (!$returnAuthorization) {
            return response()->json([
                'success' => false,
                'message' => 'Return authorization not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $returnAuthorization,
            'message' => 'Return authorization retrieved successfully'
        ]);
    }

    /**
     * Update the specified return authorization
     */
    public function update(Request $request, $id): JsonResponse
    {
        $returnAuthorization = ReturnAuthorization::find($id);

        if (!$returnAuthorization) {
            return response()->json([
                'success' => false,
                'message' => 'Return authorization not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,approved,rejected,in_transit,received,processed,completed,cancelled',
            'internal_notes' => 'nullable|string',
            'expected_return_date' => 'nullable|date',
            'return_shipping_info' => 'nullable|array'
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

            $oldStatus = $returnAuthorization->status;
            $returnAuthorization->update($request->only([
                'status',
                'internal_notes',
                'expected_return_date',
                'return_shipping_info'
            ]));

            // Handle status changes
            if ($request->has('status') && $oldStatus !== $request->status) {
                $this->handleStatusChange($returnAuthorization, $oldStatus, $request->status);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $returnAuthorization->load(['customer', 'warehouse', 'items.product']),
                'message' => 'Return authorization updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update return authorization: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve return authorization
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $returnAuthorization = ReturnAuthorization::find($id);

        if (!$returnAuthorization) {
            return response()->json([
                'success' => false,
                'message' => 'Return authorization not found'
            ], 404);
        }

        if ($returnAuthorization->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending return authorizations can be approved'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.id' => 'required|exists:return_authorization_items,id',
            'items.*.approved_quantity' => 'required|integer|min:0',
            'internal_notes' => 'nullable|string'
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

            // Update item approved quantities
            foreach ($request->items as $itemData) {
                $item = ReturnAuthorizationItem::find($itemData['id']);
                if ($item && $item->return_authorization_id == $id) {
                    $item->update([
                        'approved_quantity' => min($itemData['approved_quantity'], $item->requested_quantity)
                    ]);
                }
            }

            // Update return authorization
            $returnAuthorization->update([
                'status' => 'approved',
                'approved_date' => now(),
                'approved_by' => auth()->id(),
                'internal_notes' => $request->internal_notes
            ]);

            // Fire event
            $this->fireTransactionalEvent('returns.authorization.approved', [
                'return_authorization_id' => $returnAuthorization->id,
                'rma_number' => $returnAuthorization->rma_number,
                'approved_by' => auth()->id(),
                'approved_items' => count($request->items)
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $returnAuthorization->load(['customer', 'warehouse', 'items.product']),
                'message' => 'Return authorization approved successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve return authorization: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get return authorization analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = ReturnAuthorization::whereBetween('requested_date', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $analytics = [
            'total_returns' => $query->count(),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_type' => $query->groupBy('return_type')->selectRaw('return_type, count(*) as count')->pluck('count', 'return_type'),
            'total_value' => $query->sum('estimated_value'),
            'total_refunds' => $query->sum('actual_refund_amount'),
            'average_processing_time' => $this->getAverageProcessingTime($query),
            'top_customers' => $this->getTopCustomers($query),
            'monthly_trend' => $this->getMonthlyTrend($dateFrom, $dateTo, $warehouseId)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Return authorization analytics retrieved successfully'
        ]);
    }

    /**
     * Handle status changes
     */
    private function handleStatusChange($returnAuthorization, $oldStatus, $newStatus)
    {
        $statusTimestamps = [
            'approved' => ['approved_date' => now(), 'approved_by' => auth()->id()],
            'received' => ['received_date' => now()],
            'processed' => ['processed_date' => now(), 'processed_by' => auth()->id()]
        ];

        if (isset($statusTimestamps[$newStatus])) {
            $returnAuthorization->update($statusTimestamps[$newStatus]);
        }

        // Fire status change event
        $this->fireTransactionalEvent('returns.authorization.status_changed', [
            'return_authorization_id' => $returnAuthorization->id,
            'rma_number' => $returnAuthorization->rma_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);
    }

    /**
     * Get average processing time
     */
    private function getAverageProcessingTime($query)
    {
        return $query->whereNotNull('processed_date')
            ->whereNotNull('requested_date')
            ->selectRaw('AVG(DATEDIFF(processed_date, requested_date)) as avg_days')
            ->value('avg_days') ?? 0;
    }

    /**
     * Get top customers by return count
     */
    private function getTopCustomers($query)
    {
        return $query->with('customer')
            ->groupBy('customer_id')
            ->selectRaw('customer_id, count(*) as return_count, sum(estimated_value) as total_value')
            ->orderBy('return_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'customer' => $item->customer,
                    'return_count' => $item->return_count,
                    'total_value' => $item->total_value
                ];
            });
    }

    /**
     * Get monthly trend data
     */
    private function getMonthlyTrend($dateFrom, $dateTo, $warehouseId)
    {
        $query = ReturnAuthorization::whereBetween('requested_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE_FORMAT(requested_date, "%Y-%m") as month, count(*) as count, sum(estimated_value) as value');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->groupBy('month')
            ->orderBy('month')
            ->get();
    }
}