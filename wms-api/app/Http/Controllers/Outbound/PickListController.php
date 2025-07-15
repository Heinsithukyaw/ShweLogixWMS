<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\PickList;
use App\Models\Outbound\PickListItem;
use App\Models\PickWave;
use App\Services\Outbound\PickListService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PickListController extends Controller
{
    protected $pickListService;

    public function __construct(PickListService $pickListService)
    {
        $this->pickListService = $pickListService;
    }

    /**
     * Get all pick lists with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = PickList::with([
            'pickWave',
            'assignedTo',
            'createdBy',
            'pickListItems.product',
            'pickListItems.location'
        ]);

        // Apply filters
        if ($request->has('status')) {
            $query->where('pick_status', $request->status);
        }

        if ($request->has('pick_type')) {
            $query->where('pick_type', $request->pick_type);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('pick_wave_id')) {
            $query->where('pick_wave_id', $request->pick_wave_id);
        }

        if ($request->has('overdue')) {
            $query->whereHas('pickListItems', function($q) {
                $q->where('estimated_time', '<', now()->subHours(2));
            });
        }

        $pickLists = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $pickLists,
            'message' => 'Pick lists retrieved successfully'
        ]);
    }

    /**
     * Generate pick lists for a wave
     */
    public function generateForWave(Request $request, $waveId): JsonResponse
    {
        $request->validate([
            'pick_type' => 'required|in:single,batch,zone,cluster,wave',
            'pick_method' => 'required|in:discrete,batch,zone,cluster',
            'batch_size' => 'nullable|integer|min:1|max:50',
            'zone_assignments' => 'nullable|array',
            'optimization_rules' => 'nullable|array',
        ]);

        try {
            $pickWave = PickWave::findOrFail($waveId);
            
            $pickLists = $this->pickListService->generatePickLists(
                $pickWave,
                $request->pick_type,
                $request->pick_method,
                $request->batch_size,
                $request->zone_assignments,
                $request->optimization_rules
            );

            return response()->json([
                'success' => true,
                'data' => $pickLists,
                'message' => 'Pick lists generated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pick list generation failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get pick list details
     */
    public function show($id): JsonResponse
    {
        $pickList = PickList::with([
            'pickWave',
            'assignedTo',
            'createdBy',
            'pickListItems' => function($query) {
                $query->orderBy('pick_sequence');
            },
            'pickListItems.product',
            'pickListItems.location',
            'pickListItems.salesOrder',
            'pickListItems.pickConfirmations',
            'pickListItems.pickExceptions'
        ])->findOrFail($id);

        // Add calculated fields
        $pickList->completion_percentage = $pickList->getCompletionPercentage();
        $pickList->is_overdue = $pickList->isOverdue();
        $pickList->actual_efficiency = $pickList->getActualEfficiency();

        return response()->json([
            'success' => true,
            'data' => $pickList,
            'message' => 'Pick list retrieved successfully'
        ]);
    }

    /**
     * Assign pick list to employee
     */
    public function assign(Request $request, $id): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        try {
            $pickList = PickList::findOrFail($id);
            
            if ($pickList->pick_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pick list cannot be assigned in current status'
                ], 400);
            }

            $pickList->assign($request->employee_id);

            return response()->json([
                'success' => true,
                'data' => $pickList->fresh(),
                'message' => 'Pick list assigned successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Start pick list
     */
    public function start($id): JsonResponse
    {
        try {
            $pickList = PickList::findOrFail($id);
            
            if ($pickList->pick_status !== 'assigned') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pick list must be assigned before starting'
                ], 400);
            }

            $pickList->start();

            return response()->json([
                'success' => true,
                'data' => $pickList->fresh(),
                'message' => 'Pick list started successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Start failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Complete pick list
     */
    public function complete($id): JsonResponse
    {
        try {
            $pickList = PickList::findOrFail($id);
            
            if ($pickList->pick_status !== 'in_progress') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pick list must be in progress to complete'
                ], 400);
            }

            // Check if all items are picked
            $unpickedItems = $pickList->pickListItems()
                ->where('pick_status', 'pending')
                ->count();
                
            if ($unpickedItems > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot complete pick list with {$unpickedItems} unpicked items"
                ], 400);
            }

            $pickList->complete();

            return response()->json([
                'success' => true,
                'data' => $pickList->fresh(),
                'message' => 'Pick list completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Completion failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Pick item from pick list
     */
    public function pickItem(Request $request, $id, $itemId): JsonResponse
    {
        $request->validate([
            'quantity_picked' => 'required|numeric|min:0.001',
            'employee_id' => 'required|exists:employees,id',
            'confirmation_method' => 'required|in:barcode,rfid,manual',
            'barcode_scanned' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $pickList = PickList::findOrFail($id);
            $pickListItem = PickListItem::where('pick_list_id', $id)
                ->where('id', $itemId)
                ->firstOrFail();

            if (!$pickListItem->canBePicked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item cannot be picked in current status'
                ], 400);
            }

            $pickedQuantity = $pickListItem->pick(
                $request->quantity_picked,
                $request->employee_id,
                $request->confirmation_method,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'pick_list_item' => $pickListItem->fresh(),
                    'picked_quantity' => $pickedQuantity,
                    'pick_list_progress' => $pickList->fresh()->getCompletionPercentage()
                ],
                'message' => 'Item picked successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pick failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Create pick exception
     */
    public function createException(Request $request, $id, $itemId): JsonResponse
    {
        $request->validate([
            'exception_type' => 'required|in:short_pick,damage,location_empty,product_mismatch,system_error',
            'description' => 'required|string',
            'employee_id' => 'required|exists:employees,id',
        ]);

        try {
            $pickListItem = PickListItem::where('pick_list_id', $id)
                ->where('id', $itemId)
                ->firstOrFail();

            $exception = $pickListItem->createException(
                $request->exception_type,
                $request->description,
                $request->employee_id
            );

            return response()->json([
                'success' => true,
                'data' => $exception,
                'message' => 'Exception created successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception creation failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Optimize pick sequence
     */
    public function optimizeSequence($id): JsonResponse
    {
        try {
            $pickList = PickList::findOrFail($id);
            
            if ($pickList->pick_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only optimize pending pick lists'
                ], 400);
            }

            $optimizedSequence = $pickList->generateOptimizedSequence();

            return response()->json([
                'success' => true,
                'data' => [
                    'optimized_sequence' => $optimizedSequence,
                    'pick_list' => $pickList->fresh()
                ],
                'message' => 'Pick sequence optimized successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Optimization failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get pick list performance metrics
     */
    public function performance($id): JsonResponse
    {
        $pickList = PickList::with([
            'pickListItems',
            'pickConfirmations',
            'pickExceptions'
        ])->findOrFail($id);

        $metrics = $this->pickListService->calculatePerformanceMetrics($pickList);

        return response()->json([
            'success' => true,
            'data' => $metrics,
            'message' => 'Performance metrics retrieved successfully'
        ]);
    }

    /**
     * Bulk assign pick lists
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        $request->validate([
            'pick_list_ids' => 'required|array',
            'pick_list_ids.*' => 'exists:pick_lists,id',
            'employee_id' => 'required|exists:employees,id',
        ]);

        try {
            $results = $this->pickListService->bulkAssignPickLists(
                $request->pick_list_ids,
                $request->employee_id
            );

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Bulk assignment completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk assignment failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get pick list summary for dashboard
     */
    public function summary(Request $request): JsonResponse
    {
        $summary = $this->pickListService->getPickListSummary($request->all());

        return response()->json([
            'success' => true,
            'data' => $summary,
            'message' => 'Pick list summary retrieved successfully'
        ]);
    }
}