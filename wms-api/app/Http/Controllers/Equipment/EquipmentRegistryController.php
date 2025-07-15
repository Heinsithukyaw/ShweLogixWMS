<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Controller;
use App\Models\Equipment\EquipmentRegistry;
use App\Models\Equipment\EquipmentCategory;
use App\Models\Equipment\EquipmentMaintenanceSchedule;
use App\Models\Equipment\EquipmentAlert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;

class EquipmentRegistryController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of equipment
     */
    public function index(Request $request): JsonResponse
    {
        $query = EquipmentRegistry::with(['category', 'warehouse', 'currentLocation', 'assignedOperator']);

        // Apply filters
        if ($request->has('warehouse_id')) {
            $query->byWarehouse($request->warehouse_id);
        }

        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('condition')) {
            $query->where('condition', $request->condition);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('equipment_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        $equipment = $query->orderBy('equipment_code')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $equipment,
            'message' => 'Equipment retrieved successfully'
        ]);
    }

    /**
     * Store a newly created equipment
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:equipment_categories,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'current_location_id' => 'nullable|exists:locations,id',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date|after:purchase_date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,maintenance,repair,retired,disposed,lost',
            'condition' => 'required|in:excellent,good,fair,poor,critical',
            'specifications' => 'nullable|array',
            'attachments' => 'nullable|array',
            'notes' => 'nullable|string',
            'is_mobile' => 'boolean',
            'requires_operator' => 'boolean',
            'assigned_operator' => 'nullable|exists:employees,id'
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

            // Generate equipment code
            $category = EquipmentCategory::find($request->category_id);
            $equipmentCode = $this->generateEquipmentCode($category->category_code);

            $equipment = EquipmentRegistry::create([
                'equipment_code' => $equipmentCode,
                'name' => $request->name,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'warehouse_id' => $request->warehouse_id,
                'current_location_id' => $request->current_location_id,
                'manufacturer' => $request->manufacturer,
                'model' => $request->model,
                'serial_number' => $request->serial_number,
                'purchase_date' => $request->purchase_date,
                'warranty_expiry' => $request->warranty_expiry,
                'purchase_cost' => $request->purchase_cost ?? 0,
                'current_value' => $request->current_value ?? $request->purchase_cost ?? 0,
                'status' => $request->status,
                'condition' => $request->condition,
                'specifications' => $request->specifications ?? [],
                'attachments' => $request->attachments ?? [],
                'notes' => $request->notes,
                'is_mobile' => $request->is_mobile ?? true,
                'requires_operator' => $request->requires_operator ?? false,
                'assigned_operator' => $request->assigned_operator
            ]);

            // Create default maintenance schedules if category requires inspection
            if ($category->requires_inspection) {
                $this->createDefaultMaintenanceSchedules($equipment, $category);
            }

            // Fire event
            $this->fireTransactionalEvent('equipment.registered', [
                'equipment_id' => $equipment->id,
                'equipment_code' => $equipmentCode,
                'category_id' => $request->category_id,
                'warehouse_id' => $request->warehouse_id,
                'purchase_cost' => $request->purchase_cost ?? 0
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $equipment->load(['category', 'warehouse', 'currentLocation', 'assignedOperator']),
                'message' => 'Equipment registered successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to register equipment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified equipment
     */
    public function show($id): JsonResponse
    {
        $equipment = EquipmentRegistry::with([
            'category',
            'warehouse',
            'currentLocation',
            'assignedOperator',
            'maintenanceSchedules' => function ($query) {
                $query->where('is_active', true)->orderBy('next_due_date');
            },
            'maintenanceRecords' => function ($query) {
                $query->orderBy('scheduled_start', 'desc')->limit(10);
            },
            'alerts' => function ($query) {
                $query->where('status', 'active')->orderBy('triggered_at', 'desc');
            },
            'performanceMetrics' => function ($query) {
                $query->orderBy('metric_date', 'desc')->limit(30);
            }
        ])->find($id);

        if (!$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $equipment,
            'message' => 'Equipment retrieved successfully'
        ]);
    }

    /**
     * Update the specified equipment
     */
    public function update(Request $request, $id): JsonResponse
    {
        $equipment = EquipmentRegistry::find($id);

        if (!$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'current_location_id' => 'nullable|exists:locations,id',
            'status' => 'sometimes|in:active,maintenance,repair,retired,disposed,lost',
            'condition' => 'sometimes|in:excellent,good,fair,poor,critical',
            'current_value' => 'nullable|numeric|min:0',
            'specifications' => 'nullable|array',
            'attachments' => 'nullable|array',
            'notes' => 'nullable|string',
            'assigned_operator' => 'nullable|exists:employees,id'
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

            $oldStatus = $equipment->status;
            $oldLocation = $equipment->current_location_id;
            $oldOperator = $equipment->assigned_operator;

            $equipment->update($request->only([
                'name',
                'description',
                'current_location_id',
                'status',
                'condition',
                'current_value',
                'specifications',
                'attachments',
                'notes',
                'assigned_operator'
            ]));

            // Handle status changes
            if ($request->has('status') && $oldStatus !== $request->status) {
                $this->handleStatusChange($equipment, $oldStatus, $request->status);
            }

            // Handle location changes
            if ($request->has('current_location_id') && $oldLocation !== $request->current_location_id) {
                $this->handleLocationChange($equipment, $oldLocation, $request->current_location_id);
            }

            // Handle operator changes
            if ($request->has('assigned_operator') && $oldOperator !== $request->assigned_operator) {
                $this->handleOperatorChange($equipment, $oldOperator, $request->assigned_operator);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $equipment->load(['category', 'warehouse', 'currentLocation', 'assignedOperator']),
                'message' => 'Equipment updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update equipment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get equipment analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $categoryId = $request->get('category_id');

        $query = EquipmentRegistry::query();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $analytics = [
            'total_equipment' => $query->count(),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_condition' => $query->groupBy('condition')->selectRaw('condition, count(*) as count')->pluck('count', 'condition'),
            'by_category' => $this->getEquipmentByCategory($warehouseId),
            'total_value' => $query->sum('current_value'),
            'average_age' => $this->getAverageAge($query),
            'maintenance_due' => $this->getMaintenanceDue($warehouseId),
            'active_alerts' => $this->getActiveAlerts($warehouseId),
            'utilization_summary' => $this->getUtilizationSummary($warehouseId),
            'cost_analysis' => $this->getCostAnalysis($warehouseId)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Equipment analytics retrieved successfully'
        ]);
    }

    /**
     * Generate equipment code
     */
    private function generateEquipmentCode($categoryCode): string
    {
        $year = date('Y');
        $sequence = EquipmentRegistry::whereYear('created_at', $year)->count() + 1;
        
        return $categoryCode . '-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create default maintenance schedules
     */
    private function createDefaultMaintenanceSchedules($equipment, $category)
    {
        $schedules = [
            [
                'maintenance_type' => 'preventive',
                'task_name' => 'Regular Inspection',
                'description' => 'Regular inspection as per category requirements',
                'frequency_type' => 'daily',
                'frequency_value' => $category->default_inspection_interval_days,
                'estimated_duration_minutes' => 30,
                'is_critical' => true,
                'next_due_date' => now()->addDays($category->default_inspection_interval_days)
            ]
        ];

        foreach ($schedules as $scheduleData) {
            EquipmentMaintenanceSchedule::create(array_merge($scheduleData, [
                'equipment_id' => $equipment->id
            ]));
        }
    }

    /**
     * Handle status changes
     */
    private function handleStatusChange($equipment, $oldStatus, $newStatus)
    {
        // Create lifecycle event
        $equipment->lifecycleEvents()->create([
            'event_type' => $this->getEventTypeFromStatus($newStatus),
            'event_date' => now(),
            'performed_by' => auth()->id(),
            'description' => "Status changed from {$oldStatus} to {$newStatus}",
            'event_data' => [
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]
        ]);

        // Fire event
        $this->fireTransactionalEvent('equipment.status_changed', [
            'equipment_id' => $equipment->id,
            'equipment_code' => $equipment->equipment_code,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);

        // Create alerts for critical status changes
        if (in_array($newStatus, ['repair', 'critical'])) {
            EquipmentAlert::create([
                'equipment_id' => $equipment->id,
                'alert_type' => $newStatus === 'repair' ? 'breakdown' : 'safety',
                'severity' => 'critical',
                'status' => 'active',
                'title' => "Equipment {$newStatus}",
                'message' => "Equipment {$equipment->equipment_code} status changed to {$newStatus}",
                'triggered_at' => now(),
                'alert_data' => [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]
            ]);
        }
    }

    /**
     * Handle location changes
     */
    private function handleLocationChange($equipment, $oldLocationId, $newLocationId)
    {
        // Create lifecycle event
        $equipment->lifecycleEvents()->create([
            'event_type' => 'relocated',
            'event_date' => now(),
            'performed_by' => auth()->id(),
            'description' => 'Equipment relocated',
            'from_location_id' => $oldLocationId,
            'to_location_id' => $newLocationId
        ]);

        // Fire event
        $this->fireTransactionalEvent('equipment.relocated', [
            'equipment_id' => $equipment->id,
            'equipment_code' => $equipment->equipment_code,
            'from_location_id' => $oldLocationId,
            'to_location_id' => $newLocationId,
            'relocated_by' => auth()->id()
        ]);
    }

    /**
     * Handle operator changes
     */
    private function handleOperatorChange($equipment, $oldOperatorId, $newOperatorId)
    {
        // Create lifecycle event
        $equipment->lifecycleEvents()->create([
            'event_type' => $newOperatorId ? 'assigned' : 'unassigned',
            'event_date' => now(),
            'performed_by' => auth()->id(),
            'description' => $newOperatorId ? 'Equipment assigned to operator' : 'Equipment unassigned from operator',
            'from_operator_id' => $oldOperatorId,
            'to_operator_id' => $newOperatorId
        ]);

        // Fire event
        $this->fireTransactionalEvent('equipment.operator_changed', [
            'equipment_id' => $equipment->id,
            'equipment_code' => $equipment->equipment_code,
            'from_operator_id' => $oldOperatorId,
            'to_operator_id' => $newOperatorId,
            'changed_by' => auth()->id()
        ]);
    }

    /**
     * Get event type from status
     */
    private function getEventTypeFromStatus($status): string
    {
        $statusMap = [
            'maintenance' => 'maintenance',
            'repair' => 'repair',
            'retired' => 'retired',
            'disposed' => 'disposed'
        ];

        return $statusMap[$status] ?? 'maintenance';
    }

    /**
     * Get equipment by category
     */
    private function getEquipmentByCategory($warehouseId)
    {
        $query = EquipmentRegistry::with('category')
            ->selectRaw('category_id, count(*) as count, sum(current_value) as total_value')
            ->groupBy('category_id');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->map(function ($item) {
            return [
                'category' => $item->category,
                'count' => $item->count,
                'total_value' => $item->total_value
            ];
        });
    }

    /**
     * Get average age of equipment
     */
    private function getAverageAge($query)
    {
        return $query->whereNotNull('purchase_date')
            ->selectRaw('AVG(DATEDIFF(NOW(), purchase_date) / 365) as avg_age_years')
            ->value('avg_age_years') ?? 0;
    }

    /**
     * Get maintenance due count
     */
    private function getMaintenanceDue($warehouseId)
    {
        $query = EquipmentMaintenanceSchedule::whereHas('equipment', function ($q) use ($warehouseId) {
            if ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        })->where('is_active', true);

        return [
            'overdue' => $query->where('next_due_date', '<', now())->count(),
            'due_today' => $query->whereDate('next_due_date', now())->count(),
            'due_this_week' => $query->whereBetween('next_due_date', [now(), now()->addWeek()])->count()
        ];
    }

    /**
     * Get active alerts count
     */
    private function getActiveAlerts($warehouseId)
    {
        $query = EquipmentAlert::whereHas('equipment', function ($q) use ($warehouseId) {
            if ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        })->where('status', 'active');

        return [
            'total' => $query->count(),
            'critical' => $query->where('severity', 'critical')->count(),
            'warning' => $query->where('severity', 'warning')->count()
        ];
    }

    /**
     * Get utilization summary
     */
    private function getUtilizationSummary($warehouseId)
    {
        // This would typically come from EquipmentPerformanceMetrics
        // For now, return placeholder data
        return [
            'average_utilization' => 75.5,
            'average_efficiency' => 82.3,
            'average_availability' => 91.2
        ];
    }

    /**
     * Get cost analysis
     */
    private function getCostAnalysis($warehouseId)
    {
        // This would typically aggregate maintenance costs, operating costs, etc.
        // For now, return placeholder data
        return [
            'total_maintenance_cost' => 125000,
            'total_operating_cost' => 450000,
            'cost_per_hour' => 25.50
        ];
    }
}