<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\Labor\LaborSchedule;
use App\Models\Labor\LaborShift;
use App\Models\Labor\LaborTimeTracking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\UsesTransactionalEvents;
use Carbon\Carbon;

class LaborScheduleController extends Controller
{
    use UsesTransactionalEvents;

    /**
     * Display a listing of labor schedules
     */
    public function index(Request $request): JsonResponse
    {
        $query = LaborSchedule::with(['employee', 'shift', 'warehouse', 'supervisor']);

        // Apply filters
        if ($request->has('warehouse_id')) {
            $query->byWarehouse($request->warehouse_id);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('date_from')) {
            $query->where('schedule_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('schedule_date', '<=', $request->date_to);
        }

        if ($request->has('date')) {
            $query->forDate($request->date);
        }

        $schedules = $query->orderBy('schedule_date', 'desc')
            ->orderBy('scheduled_start')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'message' => 'Labor schedules retrieved successfully'
        ]);
    }

    /**
     * Store a newly created labor schedule
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'required|exists:labor_shifts,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'schedule_date' => 'required|date',
            'scheduled_start' => 'required|date_format:H:i',
            'scheduled_end' => 'required|date_format:H:i|after:scheduled_start',
            'supervisor_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for existing schedule on the same date
        $existingSchedule = LaborSchedule::where('employee_id', $request->employee_id)
            ->where('schedule_date', $request->schedule_date)
            ->first();

        if ($existingSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Employee already has a schedule for this date'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Calculate scheduled hours
            $scheduledStart = Carbon::createFromFormat('H:i', $request->scheduled_start);
            $scheduledEnd = Carbon::createFromFormat('H:i', $request->scheduled_end);
            $scheduledHours = $scheduledEnd->diffInHours($scheduledStart);

            $schedule = LaborSchedule::create([
                'employee_id' => $request->employee_id,
                'shift_id' => $request->shift_id,
                'warehouse_id' => $request->warehouse_id,
                'schedule_date' => $request->schedule_date,
                'scheduled_start' => $request->scheduled_start,
                'scheduled_end' => $request->scheduled_end,
                'scheduled_hours' => $scheduledHours,
                'status' => 'scheduled',
                'supervisor_id' => $request->supervisor_id,
                'notes' => $request->notes
            ]);

            // Fire event
            $this->fireTransactionalEvent('labor.schedule.created', [
                'schedule_id' => $schedule->id,
                'employee_id' => $request->employee_id,
                'warehouse_id' => $request->warehouse_id,
                'schedule_date' => $request->schedule_date,
                'scheduled_hours' => $scheduledHours
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $schedule->load(['employee', 'shift', 'warehouse', 'supervisor']),
                'message' => 'Labor schedule created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create labor schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified labor schedule
     */
    public function show($id): JsonResponse
    {
        $schedule = LaborSchedule::with([
            'employee',
            'shift',
            'warehouse',
            'supervisor',
            'timeTracking'
        ])->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Labor schedule not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $schedule,
            'message' => 'Labor schedule retrieved successfully'
        ]);
    }

    /**
     * Update the specified labor schedule
     */
    public function update(Request $request, $id): JsonResponse
    {
        $schedule = LaborSchedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Labor schedule not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'scheduled_start' => 'sometimes|date_format:H:i',
            'scheduled_end' => 'sometimes|date_format:H:i|after:scheduled_start',
            'actual_start' => 'nullable|date_format:H:i',
            'actual_end' => 'nullable|date_format:H:i|after:actual_start',
            'status' => 'sometimes|in:scheduled,checked_in,on_break,checked_out,absent,late,overtime',
            'supervisor_id' => 'nullable|exists:employees,id',
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

            $oldStatus = $schedule->status;
            
            // Update basic fields
            $updateData = $request->only([
                'scheduled_start',
                'scheduled_end',
                'actual_start',
                'actual_end',
                'status',
                'supervisor_id',
                'notes'
            ]);

            // Recalculate hours if times are updated
            if ($request->has('scheduled_start') || $request->has('scheduled_end')) {
                $start = Carbon::createFromFormat('H:i', $request->scheduled_start ?? $schedule->scheduled_start);
                $end = Carbon::createFromFormat('H:i', $request->scheduled_end ?? $schedule->scheduled_end);
                $updateData['scheduled_hours'] = $end->diffInHours($start);
            }

            if ($request->has('actual_start') && $request->has('actual_end')) {
                $actualStart = Carbon::createFromFormat('H:i', $request->actual_start);
                $actualEnd = Carbon::createFromFormat('H:i', $request->actual_end);
                $actualHours = $actualEnd->diffInHours($actualStart);
                $updateData['actual_hours'] = $actualHours;
                
                // Calculate overtime
                $scheduledHours = $updateData['scheduled_hours'] ?? $schedule->scheduled_hours;
                $updateData['overtime_hours'] = max(0, $actualHours - $scheduledHours);
            }

            $schedule->update($updateData);

            // Handle status changes
            if ($request->has('status') && $oldStatus !== $request->status) {
                $this->handleStatusChange($schedule, $oldStatus, $request->status);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $schedule->load(['employee', 'shift', 'warehouse', 'supervisor']),
                'message' => 'Labor schedule updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update labor schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check in employee
     */
    public function checkIn(Request $request, $id): JsonResponse
    {
        $schedule = LaborSchedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Labor schedule not found'
            ], 404);
        }

        if ($schedule->status !== 'scheduled') {
            return response()->json([
                'success' => false,
                'message' => 'Employee is not scheduled or already checked in'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'location' => 'nullable|string',
            'device_id' => 'nullable|string',
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

            $checkInTime = now()->format('H:i');
            $isLate = now() > Carbon::parse($schedule->schedule_date . ' ' . $schedule->scheduled_start);

            // Update schedule
            $schedule->update([
                'actual_start' => $checkInTime,
                'status' => $isLate ? 'late' : 'checked_in',
                'notes' => $request->notes
            ]);

            // Create time tracking record
            LaborTimeTracking::create([
                'employee_id' => $schedule->employee_id,
                'schedule_id' => $schedule->id,
                'warehouse_id' => $schedule->warehouse_id,
                'action' => 'check_in',
                'timestamp' => now(),
                'location' => $request->location,
                'device_id' => $request->device_id,
                'notes' => $request->notes
            ]);

            // Fire event
            $this->fireTransactionalEvent('labor.employee.checked_in', [
                'schedule_id' => $schedule->id,
                'employee_id' => $schedule->employee_id,
                'warehouse_id' => $schedule->warehouse_id,
                'check_in_time' => $checkInTime,
                'is_late' => $isLate
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $schedule->load(['employee', 'shift', 'warehouse']),
                'message' => 'Employee checked in successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to check in employee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check out employee
     */
    public function checkOut(Request $request, $id): JsonResponse
    {
        $schedule = LaborSchedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Labor schedule not found'
            ], 404);
        }

        if (!in_array($schedule->status, ['checked_in', 'on_break', 'late'])) {
            return response()->json([
                'success' => false,
                'message' => 'Employee is not checked in'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'location' => 'nullable|string',
            'device_id' => 'nullable|string',
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

            $checkOutTime = now()->format('H:i');
            
            // Calculate actual hours worked
            if ($schedule->actual_start) {
                $actualStart = Carbon::createFromFormat('H:i', $schedule->actual_start);
                $actualEnd = Carbon::createFromFormat('H:i', $checkOutTime);
                $actualHours = $actualEnd->diffInHours($actualStart);
                $overtimeHours = max(0, $actualHours - $schedule->scheduled_hours);
                
                $status = $overtimeHours > 0 ? 'overtime' : 'checked_out';
            } else {
                $actualHours = 0;
                $overtimeHours = 0;
                $status = 'checked_out';
            }

            // Update schedule
            $schedule->update([
                'actual_end' => $checkOutTime,
                'actual_hours' => $actualHours,
                'overtime_hours' => $overtimeHours,
                'status' => $status,
                'notes' => $request->notes
            ]);

            // Create time tracking record
            LaborTimeTracking::create([
                'employee_id' => $schedule->employee_id,
                'schedule_id' => $schedule->id,
                'warehouse_id' => $schedule->warehouse_id,
                'action' => 'check_out',
                'timestamp' => now(),
                'location' => $request->location,
                'device_id' => $request->device_id,
                'notes' => $request->notes
            ]);

            // Fire event
            $this->fireTransactionalEvent('labor.employee.checked_out', [
                'schedule_id' => $schedule->id,
                'employee_id' => $schedule->employee_id,
                'warehouse_id' => $schedule->warehouse_id,
                'check_out_time' => $checkOutTime,
                'actual_hours' => $actualHours,
                'overtime_hours' => $overtimeHours
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $schedule->load(['employee', 'shift', 'warehouse']),
                'message' => 'Employee checked out successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to check out employee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedule analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = LaborSchedule::whereBetween('schedule_date', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $analytics = [
            'total_schedules' => $query->count(),
            'attendance_rate' => $this->getAttendanceRate($query),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'total_scheduled_hours' => $query->sum('scheduled_hours'),
            'total_actual_hours' => $query->sum('actual_hours'),
            'total_overtime_hours' => $query->sum('overtime_hours'),
            'productivity_rate' => $this->getProductivityRate($query),
            'top_performers' => $this->getTopPerformers($query),
            'daily_trend' => $this->getDailyTrend($dateFrom, $dateTo, $warehouseId)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Labor schedule analytics retrieved successfully'
        ]);
    }

    /**
     * Handle status changes
     */
    private function handleStatusChange($schedule, $oldStatus, $newStatus)
    {
        // Fire status change event
        $this->fireTransactionalEvent('labor.schedule.status_changed', [
            'schedule_id' => $schedule->id,
            'employee_id' => $schedule->employee_id,
            'warehouse_id' => $schedule->warehouse_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id()
        ]);
    }

    /**
     * Get attendance rate
     */
    private function getAttendanceRate($query)
    {
        $total = $query->count();
        $present = $query->whereIn('status', ['checked_in', 'on_break', 'checked_out', 'late', 'overtime'])->count();
        
        return $total > 0 ? ($present / $total) * 100 : 0;
    }

    /**
     * Get productivity rate
     */
    private function getProductivityRate($query)
    {
        $totalScheduled = $query->sum('scheduled_hours');
        $totalActual = $query->sum('actual_hours');
        
        return $totalScheduled > 0 ? ($totalActual / $totalScheduled) * 100 : 0;
    }

    /**
     * Get top performers
     */
    private function getTopPerformers($query)
    {
        return $query->with('employee')
            ->selectRaw('employee_id, AVG(actual_hours) as avg_hours, SUM(overtime_hours) as total_overtime')
            ->groupBy('employee_id')
            ->orderBy('avg_hours', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'employee' => $item->employee,
                    'avg_hours' => $item->avg_hours,
                    'total_overtime' => $item->total_overtime
                ];
            });
    }

    /**
     * Get daily trend data
     */
    private function getDailyTrend($dateFrom, $dateTo, $warehouseId)
    {
        $query = LaborSchedule::whereBetween('schedule_date', [$dateFrom, $dateTo])
            ->selectRaw('schedule_date, count(*) as total_schedules, sum(actual_hours) as total_hours, sum(overtime_hours) as overtime_hours');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->groupBy('schedule_date')
            ->orderBy('schedule_date')
            ->get();
    }
}