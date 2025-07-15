<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\OrderPriority;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class OrderPriorityController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of order priorities
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrderPriority::with(['salesOrder.customer', 'warehouse']);

        // Apply filters
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('priority_level')) {
            $query->where('priority_level', $request->priority_level);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $priorities = $query->orderBy('priority_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $priorities,
            'message' => 'Order priorities retrieved successfully'
        ]);
    }

    /**
     * Store a newly created order priority
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sales_order_id' => 'required|exists:sales_orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'priority_level' => 'required|in:low,medium,high,urgent,critical',
            'priority_reason' => 'required|string',
            'requested_ship_date' => 'nullable|date',
            'customer_priority' => 'nullable|in:standard,expedited,rush,emergency',
            'business_impact' => 'nullable|string',
            'special_instructions' => 'nullable|string'
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

            // Calculate priority score
            $priorityScore = $this->calculatePriorityScore($request->all());

            $orderPriority = OrderPriority::create([
                'sales_order_id' => $request->sales_order_id,
                'warehouse_id' => $request->warehouse_id,
                'priority_level' => $request->priority_level,
                'priority_score' => $priorityScore,
                'priority_reason' => $request->priority_reason,
                'requested_ship_date' => $request->requested_ship_date,
                'customer_priority' => $request->customer_priority ?? 'standard',
                'business_impact' => $request->business_impact,
                'special_instructions' => $request->special_instructions,
                'status' => 'active',
                'created_by' => auth()->id(),
                'effective_date' => now(),
                'priority_factors' => $this->getPriorityFactors($request->all())
            ]);

            // Update sales order priority
            $this->updateSalesOrderPriority($request->sales_order_id, $priorityScore);

            // Fire event
            $this->fireTransactionalEvent('outbound.order.priority_set', [
                'order_priority_id' => $orderPriority->id,
                'sales_order_id' => $request->sales_order_id,
                'priority_level' => $request->priority_level,
                'priority_score' => $priorityScore,
                'warehouse_id' => $request->warehouse_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $orderPriority->load(['salesOrder.customer', 'warehouse']),
                'message' => 'Order priority set successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to set order priority: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order priority
     */
    public function show($id): JsonResponse
    {
        $orderPriority = OrderPriority::with([
            'salesOrder.customer',
            'salesOrder.items.product',
            'warehouse',
            'createdBy'
        ])->find($id);

        if (!$orderPriority) {
            return response()->json([
                'success' => false,
                'message' => 'Order priority not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $orderPriority,
            'message' => 'Order priority retrieved successfully'
        ]);
    }

    /**
     * Update the specified order priority
     */
    public function update(Request $request, $id): JsonResponse
    {
        $orderPriority = OrderPriority::find($id);

        if (!$orderPriority) {
            return response()->json([
                'success' => false,
                'message' => 'Order priority not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'priority_level' => 'sometimes|in:low,medium,high,urgent,critical',
            'priority_reason' => 'sometimes|string',
            'requested_ship_date' => 'nullable|date',
            'customer_priority' => 'nullable|in:standard,expedited,rush,emergency',
            'business_impact' => 'nullable|string',
            'special_instructions' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,expired'
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

            $oldPriorityLevel = $orderPriority->priority_level;
            $oldPriorityScore = $orderPriority->priority_score;

            // Recalculate priority score if priority level changed
            $updateData = $request->only([
                'priority_level',
                'priority_reason',
                'requested_ship_date',
                'customer_priority',
                'business_impact',
                'special_instructions',
                'status'
            ]);

            if ($request->has('priority_level')) {
                $updateData['priority_score'] = $this->calculatePriorityScore(array_merge(
                    $orderPriority->toArray(),
                    $request->all()
                ));
                $updateData['priority_factors'] = $this->getPriorityFactors(array_merge(
                    $orderPriority->toArray(),
                    $request->all()
                ));
            }

            $orderPriority->update($updateData);

            // Update sales order priority if score changed
            if (isset($updateData['priority_score']) && $updateData['priority_score'] !== $oldPriorityScore) {
                $this->updateSalesOrderPriority($orderPriority->sales_order_id, $updateData['priority_score']);
            }

            // Fire event if priority changed
            if ($request->has('priority_level') && $oldPriorityLevel !== $request->priority_level) {
                $this->fireTransactionalEvent('outbound.order.priority_changed', [
                    'order_priority_id' => $orderPriority->id,
                    'sales_order_id' => $orderPriority->sales_order_id,
                    'old_priority_level' => $oldPriorityLevel,
                    'new_priority_level' => $request->priority_level,
                    'old_priority_score' => $oldPriorityScore,
                    'new_priority_score' => $updateData['priority_score'] ?? $oldPriorityScore
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $orderPriority->load(['salesOrder.customer', 'warehouse']),
                'message' => 'Order priority updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order priority: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update order priorities
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'priorities' => 'required|array',
            'priorities.*.id' => 'required|exists:order_priorities,id',
            'priorities.*.priority_level' => 'required|in:low,medium,high,urgent,critical',
            'priorities.*.priority_reason' => 'sometimes|string'
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

            $updatedCount = 0;
            $results = [];

            foreach ($request->priorities as $priorityData) {
                $orderPriority = OrderPriority::find($priorityData['id']);
                
                if ($orderPriority) {
                    $newScore = $this->calculatePriorityScore(array_merge(
                        $orderPriority->toArray(),
                        $priorityData
                    ));

                    $orderPriority->update([
                        'priority_level' => $priorityData['priority_level'],
                        'priority_score' => $newScore,
                        'priority_reason' => $priorityData['priority_reason'] ?? $orderPriority->priority_reason,
                        'priority_factors' => $this->getPriorityFactors(array_merge(
                            $orderPriority->toArray(),
                            $priorityData
                        ))
                    ]);

                    $this->updateSalesOrderPriority($orderPriority->sales_order_id, $newScore);
                    $updatedCount++;

                    $results[] = [
                        'id' => $orderPriority->id,
                        'sales_order_id' => $orderPriority->sales_order_id,
                        'priority_level' => $priorityData['priority_level'],
                        'priority_score' => $newScore
                    ];
                }
            }

            // Fire bulk update event
            $this->fireTransactionalEvent('outbound.order.priorities_bulk_updated', [
                'updated_count' => $updatedCount,
                'results' => $results,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'updated_count' => $updatedCount,
                    'results' => $results
                ],
                'message' => "Successfully updated {$updatedCount} order priorities"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update priorities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get priority analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = OrderPriority::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $analytics = [
            'total_priorities' => $query->count(),
            'by_level' => $query->groupBy('priority_level')
                ->selectRaw('priority_level, count(*) as count')
                ->pluck('count', 'priority_level'),
            'by_status' => $query->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status'),
            'average_score' => $query->avg('priority_score'),
            'high_priority_count' => $query->whereIn('priority_level', ['high', 'urgent', 'critical'])->count(),
            'overdue_priorities' => $this->getOverduePriorities($warehouseId),
            'priority_trends' => $this->getPriorityTrends($dateFrom, $dateTo, $warehouseId),
            'top_priority_reasons' => $this->getTopPriorityReasons($query)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Priority analytics retrieved successfully'
        ]);
    }

    /**
     * Calculate priority score based on multiple factors
     */
    private function calculatePriorityScore(array $data): int
    {
        $score = 0;

        // Base score by priority level
        $levelScores = [
            'low' => 10,
            'medium' => 30,
            'high' => 60,
            'urgent' => 80,
            'critical' => 100
        ];

        $score += $levelScores[$data['priority_level']] ?? 30;

        // Customer priority adjustment
        $customerPriorityAdjustment = [
            'standard' => 0,
            'expedited' => 10,
            'rush' => 20,
            'emergency' => 30
        ];

        $score += $customerPriorityAdjustment[$data['customer_priority'] ?? 'standard'];

        // Requested ship date urgency
        if (isset($data['requested_ship_date'])) {
            $daysUntilShip = now()->diffInDays($data['requested_ship_date'], false);
            if ($daysUntilShip <= 1) {
                $score += 20;
            } elseif ($daysUntilShip <= 3) {
                $score += 10;
            } elseif ($daysUntilShip <= 7) {
                $score += 5;
            }
        }

        return min(100, max(0, $score));
    }

    /**
     * Get priority factors for analysis
     */
    private function getPriorityFactors(array $data): array
    {
        return [
            'priority_level' => $data['priority_level'],
            'customer_priority' => $data['customer_priority'] ?? 'standard',
            'has_requested_ship_date' => isset($data['requested_ship_date']),
            'days_until_ship' => isset($data['requested_ship_date']) 
                ? now()->diffInDays($data['requested_ship_date'], false) 
                : null,
            'has_business_impact' => !empty($data['business_impact']),
            'has_special_instructions' => !empty($data['special_instructions'])
        ];
    }

    /**
     * Update sales order priority
     */
    private function updateSalesOrderPriority(int $salesOrderId, int $priorityScore): void
    {
        SalesOrder::where('id', $salesOrderId)->update([
            'priority_score' => $priorityScore,
            'updated_at' => now()
        ]);
    }

    /**
     * Get overdue priorities
     */
    private function getOverduePriorities($warehouseId): int
    {
        $query = OrderPriority::where('status', 'active')
            ->whereNotNull('requested_ship_date')
            ->where('requested_ship_date', '<', now());

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->count();
    }

    /**
     * Get priority trends
     */
    private function getPriorityTrends($dateFrom, $dateTo, $warehouseId): array
    {
        $query = OrderPriority::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, priority_level, count(*) as count')
            ->groupBy('date', 'priority_level')
            ->orderBy('date');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->groupBy('date')->map(function ($dayData) {
            return $dayData->pluck('count', 'priority_level');
        })->toArray();
    }

    /**
     * Get top priority reasons
     */
    private function getTopPriorityReasons($query): array
    {
        return $query->selectRaw('priority_reason, count(*) as count')
            ->groupBy('priority_reason')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'priority_reason')
            ->toArray();
    }
}