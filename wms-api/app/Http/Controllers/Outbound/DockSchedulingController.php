<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\LoadingDock;
use App\Models\Outbound\DockSchedule;
use App\Models\Outbound\LoadPlan;
use App\Models\Outbound\LoadingConfirmation;
use App\Models\ShippingCarrier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DockSchedulingController extends Controller
{
    /**
     * Get all loading docks
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getLoadingDocks(Request $request)
    {
        $query = LoadingDock::with('warehouse');
        
        // Filter by warehouse if provided
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        // Filter by status if provided
        if ($request->has('dock_status')) {
            $query->where('dock_status', $request->dock_status);
        }
        
        // Filter by type if provided
        if ($request->has('dock_type')) {
            $query->where('dock_type', $request->dock_type);
        } else {
            // Default to outbound docks
            $query->where('dock_type', 'outbound');
        }
        
        $loadingDocks = $query->orderBy('dock_name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $loadingDocks
        ]);
    }

    /**
     * Get a specific loading dock
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getLoadingDock($id)
    {
        $loadingDock = LoadingDock::with('warehouse')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $loadingDock
        ]);
    }

    /**
     * Create a new loading dock
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createLoadingDock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dock_name' => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'dock_type' => 'required|in:outbound,inbound,cross_dock',
            'dock_status' => 'required|in:available,occupied,maintenance,closed',
            'dock_capabilities' => 'nullable|json',
            'max_vehicle_length_m' => 'nullable|numeric',
            'max_vehicle_height_m' => 'nullable|numeric',
            'has_dock_leveler' => 'boolean',
            'has_dock_seal' => 'boolean',
            'equipment_available' => 'nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate a unique dock code
        $dockCode = 'LD-' . strtoupper(Str::random(6));
        
        $loadingDock = LoadingDock::create([
            'dock_code' => $dockCode,
            'dock_name' => $request->dock_name,
            'warehouse_id' => $request->warehouse_id,
            'dock_type' => $request->dock_type,
            'dock_status' => $request->dock_status,
            'dock_capabilities' => $request->dock_capabilities,
            'max_vehicle_length_m' => $request->max_vehicle_length_m,
            'max_vehicle_height_m' => $request->max_vehicle_height_m,
            'has_dock_leveler' => $request->has_dock_leveler ?? false,
            'has_dock_seal' => $request->has_dock_seal ?? false,
            'equipment_available' => $request->equipment_available
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Loading dock created successfully',
            'data' => $loadingDock
        ], 201);
    }

    /**
     * Update a loading dock
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateLoadingDock(Request $request, $id)
    {
        $loadingDock = LoadingDock::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'dock_name' => 'string|max:255',
            'dock_type' => 'in:outbound,inbound,cross_dock',
            'dock_status' => 'in:available,occupied,maintenance,closed',
            'dock_capabilities' => 'nullable|json',
            'max_vehicle_length_m' => 'nullable|numeric',
            'max_vehicle_height_m' => 'nullable|numeric',
            'has_dock_leveler' => 'boolean',
            'has_dock_seal' => 'boolean',
            'equipment_available' => 'nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $loadingDock->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Loading dock updated successfully',
            'data' => $loadingDock
        ]);
    }

    /**
     * Get all dock schedules
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getDockSchedules(Request $request)
    {
        $query = DockSchedule::with(['loadingDock', 'carrier', 'loadPlan']);
        
        // Filter by dock if provided
        if ($request->has('loading_dock_id')) {
            $query->where('loading_dock_id', $request->loading_dock_id);
        }
        
        // Filter by date range if provided
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('scheduled_date', [$request->from_date, $request->to_date]);
        } else if ($request->has('date')) {
            $query->where('scheduled_date', $request->date);
        }
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('appointment_status', $request->status);
        }
        
        $dockSchedules = $query->orderBy('scheduled_date')
            ->orderBy('scheduled_start_time')
            ->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $dockSchedules
        ]);
    }

    /**
     * Get a specific dock schedule
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getDockSchedule($id)
    {
        $dockSchedule = DockSchedule::with([
            'loadingDock', 
            'carrier', 
            'loadPlan',
            'loadingConfirmation'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $dockSchedule
        ]);
    }

    /**
     * Create a new dock schedule
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createDockSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loading_dock_id' => 'required|exists:loading_docks,id',
            'load_plan_id' => 'nullable|exists:load_plans,id',
            'shipping_carrier_id' => 'required|exists:shipping_carriers,id',
            'scheduled_date' => 'required|date',
            'scheduled_start_time' => 'required',
            'scheduled_end_time' => 'required',
            'driver_name' => 'nullable|string',
            'vehicle_license' => 'nullable|string',
            'trailer_number' => 'nullable|string',
            'special_instructions' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check if dock is available for the requested time
        $conflictingAppointments = DockSchedule::where('loading_dock_id', $request->loading_dock_id)
            ->where('scheduled_date', $request->scheduled_date)
            ->where(function($query) use ($request) {
                $query->whereBetween('scheduled_start_time', [$request->scheduled_start_time, $request->scheduled_end_time])
                    ->orWhereBetween('scheduled_end_time', [$request->scheduled_start_time, $request->scheduled_end_time])
                    ->orWhere(function($q) use ($request) {
                        $q->where('scheduled_start_time', '<=', $request->scheduled_start_time)
                          ->where('scheduled_end_time', '>=', $request->scheduled_end_time);
                    });
            })
            ->where('appointment_status', '!=', 'cancelled')
            ->count();
        
        if ($conflictingAppointments > 0) {
            return response()->json([
                'success' => false,
                'message' => 'The selected dock is not available for the requested time'
            ], 422);
        }
        
        // Check if the dock is in available status
        $loadingDock = LoadingDock::find($request->loading_dock_id);
        if ($loadingDock->dock_status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'The selected dock is not available for scheduling'
            ], 422);
        }
        
        $dockSchedule = DockSchedule::create([
            'loading_dock_id' => $request->loading_dock_id,
            'load_plan_id' => $request->load_plan_id,
            'shipping_carrier_id' => $request->shipping_carrier_id,
            'scheduled_date' => $request->scheduled_date,
            'scheduled_start_time' => $request->scheduled_start_time,
            'scheduled_end_time' => $request->scheduled_end_time,
            'appointment_status' => 'scheduled',
            'driver_name' => $request->driver_name,
            'vehicle_license' => $request->vehicle_license,
            'trailer_number' => $request->trailer_number,
            'special_instructions' => $request->special_instructions,
            'scheduled_by' => auth()->id()
        ]);
        
        // If load plan is provided, update its status
        if ($request->load_plan_id) {
            $loadPlan = LoadPlan::find($request->load_plan_id);
            $loadPlan->load_status = 'planned';
            $loadPlan->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Dock schedule created successfully',
            'data' => $dockSchedule
        ], 201);
    }

    /**
     * Update a dock schedule
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateDockSchedule(Request $request, $id)
    {
        $dockSchedule = DockSchedule::findOrFail($id);
        
        // Only allow updates if the appointment is not completed or cancelled
        if (in_array($dockSchedule->appointment_status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment cannot be updated in its current status'
            ], 422);
        }
        
        $validator = Validator::make($request->all(), [
            'loading_dock_id' => 'exists:loading_docks,id',
            'load_plan_id' => 'nullable|exists:load_plans,id',
            'shipping_carrier_id' => 'exists:shipping_carriers,id',
            'scheduled_date' => 'date',
            'scheduled_start_time' => 'string',
            'scheduled_end_time' => 'string',
            'appointment_status' => 'in:scheduled,confirmed,in_progress,completed,cancelled,no_show',
            'driver_name' => 'nullable|string',
            'vehicle_license' => 'nullable|string',
            'trailer_number' => 'nullable|string',
            'special_instructions' => 'nullable|string',
            'actual_start_time' => 'nullable',
            'actual_end_time' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // If changing time or dock, check for conflicts
        if (($request->has('scheduled_date') || $request->has('scheduled_start_time') || 
             $request->has('scheduled_end_time') || $request->has('loading_dock_id')) &&
            $request->appointment_status !== 'cancelled') {
            
            $date = $request->scheduled_date ?? $dockSchedule->scheduled_date;
            $startTime = $request->scheduled_start_time ?? $dockSchedule->scheduled_start_time;
            $endTime = $request->scheduled_end_time ?? $dockSchedule->scheduled_end_time;
            $dockId = $request->loading_dock_id ?? $dockSchedule->loading_dock_id;
            
            $conflictingAppointments = DockSchedule::where('loading_dock_id', $dockId)
                ->where('scheduled_date', $date)
                ->where('id', '!=', $id)
                ->where(function($query) use ($startTime, $endTime) {
                    $query->whereBetween('scheduled_start_time', [$startTime, $endTime])
                        ->orWhereBetween('scheduled_end_time', [$startTime, $endTime])
                        ->orWhere(function($q) use ($startTime, $endTime) {
                            $q->where('scheduled_start_time', '<=', $startTime)
                              ->where('scheduled_end_time', '>=', $endTime);
                        });
                })
                ->where('appointment_status', '!=', 'cancelled')
                ->count();
            
            if ($conflictingAppointments > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected dock is not available for the requested time'
                ], 422);
            }
        }
        
        // If status is changing to in_progress, set actual start time
        if ($request->has('appointment_status') && 
            $request->appointment_status === 'in_progress' && 
            $dockSchedule->appointment_status !== 'in_progress') {
            $request->merge(['actual_start_time' => now()->format('H:i:s')]);
        }
        
        // If status is changing to completed, set actual end time
        if ($request->has('appointment_status') && 
            $request->appointment_status === 'completed' && 
            $dockSchedule->appointment_status !== 'completed') {
            $request->merge(['actual_end_time' => now()->format('H:i:s')]);
        }
        
        DB::beginTransaction();
        
        try {
            $dockSchedule->update($request->all());
            
            // If status is changing to completed, update loading dock status
            if ($request->has('appointment_status') && 
                $request->appointment_status === 'completed' && 
                $dockSchedule->appointment_status !== 'completed') {
                $loadingDock = LoadingDock::find($dockSchedule->loading_dock_id);
                $loadingDock->dock_status = 'available';
                $loadingDock->save();
            }
            
            // If status is changing to in_progress, update loading dock status
            if ($request->has('appointment_status') && 
                $request->appointment_status === 'in_progress' && 
                $dockSchedule->appointment_status !== 'in_progress') {
                $loadingDock = LoadingDock::find($dockSchedule->loading_dock_id);
                $loadingDock->dock_status = 'occupied';
                $loadingDock->save();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Dock schedule updated successfully',
                'data' => $dockSchedule
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update dock schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a dock schedule
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function cancelDockSchedule(Request $request, $id)
    {
        $dockSchedule = DockSchedule::findOrFail($id);
        
        // Only allow cancellation if the appointment is not completed
        if ($dockSchedule->appointment_status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Completed appointments cannot be cancelled'
            ], 422);
        }
        
        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $dockSchedule->appointment_status = 'cancelled';
        $dockSchedule->special_instructions = ($dockSchedule->special_instructions ? $dockSchedule->special_instructions . "\n" : '') . 
            "Cancelled: " . $request->cancellation_reason;
        $dockSchedule->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Dock schedule cancelled successfully',
            'data' => $dockSchedule
        ]);
    }

    /**
     * Get dock availability
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getDockAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'dock_type' => 'nullable|in:outbound,inbound,cross_dock'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get all docks for the warehouse
        $query = LoadingDock::where('warehouse_id', $request->warehouse_id);
        
        // Filter by dock type if provided
        if ($request->has('dock_type')) {
            $query->where('dock_type', $request->dock_type);
        } else {
            // Default to outbound docks
            $query->where('dock_type', 'outbound');
        }
        
        // Only include available docks
        $query->whereIn('dock_status', ['available', 'occupied']);
        
        $docks = $query->get();
        
        // Get all schedules for the date
        $schedules = DockSchedule::whereIn('loading_dock_id', $docks->pluck('id'))
            ->where('scheduled_date', $request->date)
            ->where('appointment_status', '!=', 'cancelled')
            ->get();
        
        // Define time slots (assuming 30-minute intervals)
        $startHour = 6; // 6 AM
        $endHour = 22; // 10 PM
        $interval = 30; // 30 minutes
        
        $timeSlots = [];
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += $interval) {
                $timeSlots[] = sprintf('%02d:%02d:00', $hour, $minute);
            }
        }
        
        // Build availability data
        $availability = [];
        
        foreach ($docks as $dock) {
            $dockSchedules = $schedules->where('loading_dock_id', $dock->id);
            $slotAvailability = [];
            
            foreach ($timeSlots as $slot) {
                $isAvailable = true;
                
                foreach ($dockSchedules as $schedule) {
                    $startTime = $schedule->scheduled_start_time;
                    $endTime = $schedule->scheduled_end_time;
                    
                    // Check if slot falls within a scheduled time
                    if ($slot >= $startTime && $slot < $endTime) {
                        $isAvailable = false;
                        break;
                    }
                }
                
                $slotAvailability[$slot] = $isAvailable;
            }
            
            $availability[] = [
                'dock_id' => $dock->id,
                'dock_code' => $dock->dock_code,
                'dock_name' => $dock->dock_name,
                'dock_type' => $dock->dock_type,
                'dock_status' => $dock->dock_status,
                'availability' => $slotAvailability
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'date' => $request->date,
                'warehouse_id' => $request->warehouse_id,
                'time_slots' => $timeSlots,
                'dock_availability' => $availability
            ]
        ]);
    }

    /**
     * Find available dock slots
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function findAvailableDockSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'duration_minutes' => 'required|integer|min:30|max:480',
            'dock_type' => 'nullable|in:outbound,inbound,cross_dock',
            'vehicle_length_m' => 'nullable|numeric',
            'vehicle_height_m' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get all docks for the warehouse
        $query = LoadingDock::where('warehouse_id', $request->warehouse_id);
        
        // Filter by dock type if provided
        if ($request->has('dock_type')) {
            $query->where('dock_type', $request->dock_type);
        } else {
            // Default to outbound docks
            $query->where('dock_type', 'outbound');
        }
        
        // Only include available docks
        $query->whereIn('dock_status', ['available']);
        
        // Filter by vehicle dimensions if provided
        if ($request->has('vehicle_length_m')) {
            $query->where(function($q) use ($request) {
                $q->whereNull('max_vehicle_length_m')
                  ->orWhere('max_vehicle_length_m', '>=', $request->vehicle_length_m);
            });
        }
        
        if ($request->has('vehicle_height_m')) {
            $query->where(function($q) use ($request) {
                $q->whereNull('max_vehicle_height_m')
                  ->orWhere('max_vehicle_height_m', '>=', $request->vehicle_height_m);
            });
        }
        
        $docks = $query->get();
        
        if ($docks->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No suitable docks found for the given criteria',
                'data' => []
            ]);
        }
        
        // Get all schedules for the date
        $schedules = DockSchedule::whereIn('loading_dock_id', $docks->pluck('id'))
            ->where('scheduled_date', $request->date)
            ->where('appointment_status', '!=', 'cancelled')
            ->get();
        
        // Define time slots (assuming 30-minute intervals)
        $startHour = 6; // 6 AM
        $endHour = 22; // 10 PM
        $interval = 30; // 30 minutes
        
        $timeSlots = [];
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += $interval) {
                $timeSlots[] = sprintf('%02d:%02d:00', $hour, $minute);
            }
        }
        
        // Calculate how many consecutive slots are needed
        $requiredSlots = ceil($request->duration_minutes / $interval);
        
        // Find available slots
        $availableSlots = [];
        
        foreach ($docks as $dock) {
            $dockSchedules = $schedules->where('loading_dock_id', $dock->id);
            $consecutiveAvailable = 0;
            $startSlot = null;
            
            for ($i = 0; $i < count($timeSlots); $i++) {
                $slot = $timeSlots[$i];
                $isAvailable = true;
                
                foreach ($dockSchedules as $schedule) {
                    $startTime = $schedule->scheduled_start_time;
                    $endTime = $schedule->scheduled_end_time;
                    
                    // Check if slot falls within a scheduled time
                    if ($slot >= $startTime && $slot < $endTime) {
                        $isAvailable = false;
                        break;
                    }
                }
                
                if ($isAvailable) {
                    if ($consecutiveAvailable === 0) {
                        $startSlot = $slot;
                    }
                    $consecutiveAvailable++;
                    
                    if ($consecutiveAvailable >= $requiredSlots) {
                        // Calculate end time
                        $startDateTime = Carbon::createFromFormat('H:i:s', $startSlot);
                        $endDateTime = (clone $startDateTime)->addMinutes($request->duration_minutes);
                        $endSlot = $endDateTime->format('H:i:s');
                        
                        $availableSlots[] = [
                            'dock_id' => $dock->id,
                            'dock_code' => $dock->dock_code,
                            'dock_name' => $dock->dock_name,
                            'start_time' => $startSlot,
                            'end_time' => $endSlot,
                            'duration_minutes' => $request->duration_minutes
                        ];
                        
                        // Move to next slot
                        $startSlot = null;
                        $consecutiveAvailable = 0;
                    }
                } else {
                    $startSlot = null;
                    $consecutiveAvailable = 0;
                }
            }
        }
        
        // Sort by start time
        usort($availableSlots, function($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'date' => $request->date,
                'warehouse_id' => $request->warehouse_id,
                'duration_minutes' => $request->duration_minutes,
                'available_slots' => $availableSlots
            ]
        ]);
    }

    /**
     * Get dock schedule calendar
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getDockScheduleCalendar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'dock_type' => 'nullable|in:outbound,inbound,cross_dock'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get all docks for the warehouse
        $query = LoadingDock::where('warehouse_id', $request->warehouse_id);
        
        // Filter by dock type if provided
        if ($request->has('dock_type')) {
            $query->where('dock_type', $request->dock_type);
        }
        
        $docks = $query->get();
        
        // Get all schedules for the date range
        $schedules = DockSchedule::whereIn('loading_dock_id', $docks->pluck('id'))
            ->whereBetween('scheduled_date', [$request->from_date, $request->to_date])
            ->with(['carrier', 'loadPlan'])
            ->get();
        
        // Format schedules as calendar events
        $events = [];
        
        foreach ($schedules as $schedule) {
            $dock = $docks->firstWhere('id', $schedule->loading_dock_id);
            
            // Determine event color based on status
            $color = '#3788d8'; // Default blue
            
            switch ($schedule->appointment_status) {
                case 'scheduled':
                    $color = '#3788d8'; // Blue
                    break;
                case 'confirmed':
                    $color = '#00c853'; // Green
                    break;
                case 'in_progress':
                    $color = '#ff9800'; // Orange
                    break;
                case 'completed':
                    $color = '#4caf50'; // Green
                    break;
                case 'cancelled':
                    $color = '#f44336'; // Red
                    break;
                case 'no_show':
                    $color = '#9e9e9e'; // Grey
                    break;
            }
            
            // Create event title
            $title = $dock->dock_name . ' - ' . $schedule->carrier->carrier_name;
            
            if ($schedule->load_plan_id) {
                $title .= ' - LP#' . $schedule->loadPlan->load_plan_number;
            }
            
            // Create event
            $events[] = [
                'id' => $schedule->id,
                'title' => $title,
                'start' => $schedule->scheduled_date . 'T' . $schedule->scheduled_start_time,
                'end' => $schedule->scheduled_date . 'T' . $schedule->scheduled_end_time,
                'resourceId' => $schedule->loading_dock_id,
                'color' => $color,
                'extendedProps' => [
                    'schedule_id' => $schedule->id,
                    'dock_id' => $schedule->loading_dock_id,
                    'dock_name' => $dock->dock_name,
                    'carrier_id' => $schedule->shipping_carrier_id,
                    'carrier_name' => $schedule->carrier->carrier_name,
                    'load_plan_id' => $schedule->load_plan_id,
                    'status' => $schedule->appointment_status,
                    'driver_name' => $schedule->driver_name,
                    'vehicle_license' => $schedule->vehicle_license,
                    'trailer_number' => $schedule->trailer_number,
                    'special_instructions' => $schedule->special_instructions
                ]
            ];
        }
        
        // Format resources (docks)
        $resources = $docks->map(function($dock) {
            return [
                'id' => $dock->id,
                'title' => $dock->dock_name,
                'dock_code' => $dock->dock_code,
                'dock_type' => $dock->dock_type,
                'dock_status' => $dock->dock_status
            ];
        })->toArray();
        
        return response()->json([
            'success' => true,
            'data' => [
                'warehouse_id' => $request->warehouse_id,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'resources' => $resources,
                'events' => $events
            ]
        ]);
    }
}