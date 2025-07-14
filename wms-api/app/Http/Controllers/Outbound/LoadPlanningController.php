<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\LoadPlan;
use App\Models\Outbound\LoadingDock;
use App\Models\Outbound\DockSchedule;
use App\Models\Outbound\LoadingConfirmation;
use App\Models\Outbound\Shipment;
use App\Models\ShippingCarrier;
use App\Models\Warehouse;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoadPlanningController extends Controller
{
    /**
     * Get all load plans
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getLoadPlans(Request $request)
    {
        $query = LoadPlan::with(['carrier']);
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('load_status', $request->status);
        }
        
        // Filter by date range if provided
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('planned_departure_date', [$request->from_date, $request->to_date]);
        }
        
        // Filter by carrier if provided
        if ($request->has('carrier_id')) {
            $query->where('shipping_carrier_id', $request->carrier_id);
        }
        
        $loadPlans = $query->orderBy('planned_departure_date', 'desc')
            ->orderBy('planned_departure_time', 'desc')
            ->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $loadPlans
        ]);
    }

    /**
     * Get a specific load plan
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getLoadPlan($id)
    {
        $loadPlan = LoadPlan::with([
            'carrier',
            'shipments',
            'dockSchedule',
            'loadingConfirmation'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $loadPlan
        ]);
    }

    /**
     * Create a new load plan
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createLoadPlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_carrier_id' => 'required|exists:shipping_carriers,id',
            'vehicle_type' => 'required|string',
            'vehicle_id' => 'nullable|string',
            'shipment_ids' => 'required|json',
            'total_weight_kg' => 'required|numeric',
            'total_volume_cm3' => 'required|numeric',
            'vehicle_capacity_weight_kg' => 'required|numeric',
            'vehicle_capacity_volume_cm3' => 'required|numeric',
            'loading_sequence' => 'nullable|json',
            'planned_departure_date' => 'required|date',
            'planned_departure_time' => 'required',
            'loading_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate a unique load plan number
        $loadPlanNumber = 'LP-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        
        // Calculate utilization percentages
        $utilizationWeight = ($request->total_weight_kg / $request->vehicle_capacity_weight_kg) * 100;
        $utilizationVolume = ($request->total_volume_cm3 / $request->vehicle_capacity_volume_cm3) * 100;
        
        $loadPlan = LoadPlan::create([
            'load_plan_number' => $loadPlanNumber,
            'shipping_carrier_id' => $request->shipping_carrier_id,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_id' => $request->vehicle_id,
            'shipment_ids' => $request->shipment_ids,
            'load_status' => 'planned',
            'total_weight_kg' => $request->total_weight_kg,
            'total_volume_cm3' => $request->total_volume_cm3,
            'vehicle_capacity_weight_kg' => $request->vehicle_capacity_weight_kg,
            'vehicle_capacity_volume_cm3' => $request->vehicle_capacity_volume_cm3,
            'utilization_weight_pct' => round($utilizationWeight, 2),
            'utilization_volume_pct' => round($utilizationVolume, 2),
            'loading_sequence' => $request->loading_sequence,
            'planned_departure_date' => $request->planned_departure_date,
            'planned_departure_time' => $request->planned_departure_time,
            'loading_notes' => $request->loading_notes,
            'created_by' => auth()->id()
        ]);
        
        // Update shipment statuses
        $shipmentIds = json_decode($request->shipment_ids, true);
        Shipment::whereIn('id', $shipmentIds)->update(['shipment_status' => 'ready']);
        
        return response()->json([
            'success' => true,
            'message' => 'Load plan created successfully',
            'data' => $loadPlan
        ], 201);
    }

    /**
     * Update a load plan
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateLoadPlan(Request $request, $id)
    {
        $loadPlan = LoadPlan::findOrFail($id);
        
        // Only allow updates if the load plan is still in planned status
        if ($loadPlan->load_status !== 'planned') {
            return response()->json([
                'success' => false,
                'message' => 'Load plan cannot be updated in its current status'
            ], 422);
        }
        
        $validator = Validator::make($request->all(), [
            'vehicle_type' => 'string',
            'vehicle_id' => 'nullable|string',
            'shipment_ids' => 'json',
            'total_weight_kg' => 'numeric',
            'total_volume_cm3' => 'numeric',
            'vehicle_capacity_weight_kg' => 'numeric',
            'vehicle_capacity_volume_cm3' => 'numeric',
            'loading_sequence' => 'nullable|json',
            'planned_departure_date' => 'date',
            'planned_departure_time' => 'string',
            'loading_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // If shipment IDs are being updated, handle the changes
        if ($request->has('shipment_ids')) {
            $oldShipmentIds = json_decode($loadPlan->shipment_ids, true);
            $newShipmentIds = json_decode($request->shipment_ids, true);
            
            // Find removed shipments
            $removedShipmentIds = array_diff($oldShipmentIds, $newShipmentIds);
            
            // Find added shipments
            $addedShipmentIds = array_diff($newShipmentIds, $oldShipmentIds);
            
            // Update status of removed shipments
            if (!empty($removedShipmentIds)) {
                Shipment::whereIn('id', $removedShipmentIds)->update(['shipment_status' => 'planned']);
            }
            
            // Update status of added shipments
            if (!empty($addedShipmentIds)) {
                Shipment::whereIn('id', $addedShipmentIds)->update(['shipment_status' => 'ready']);
            }
        }
        
        // If weight/volume data is provided, recalculate utilization
        if ($request->has('total_weight_kg') && $request->has('vehicle_capacity_weight_kg')) {
            $utilizationWeight = ($request->total_weight_kg / $request->vehicle_capacity_weight_kg) * 100;
            $request->merge(['utilization_weight_pct' => round($utilizationWeight, 2)]);
        }
        
        if ($request->has('total_volume_cm3') && $request->has('vehicle_capacity_volume_cm3')) {
            $utilizationVolume = ($request->total_volume_cm3 / $request->vehicle_capacity_volume_cm3) * 100;
            $request->merge(['utilization_volume_pct' => round($utilizationVolume, 2)]);
        }
        
        $loadPlan->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Load plan updated successfully',
            'data' => $loadPlan
        ]);
    }

    /**
     * Cancel a load plan
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function cancelLoadPlan(Request $request, $id)
    {
        $loadPlan = LoadPlan::findOrFail($id);
        
        // Only allow cancellation if the load plan is still in planned status
        if (!in_array($loadPlan->load_status, ['planned', 'loading'])) {
            return response()->json([
                'success' => false,
                'message' => 'Load plan cannot be cancelled in its current status'
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
        
        DB::beginTransaction();
        
        try {
            // Update load plan status
            $loadPlan->load_status = 'cancelled';
            $loadPlan->loading_notes = ($loadPlan->loading_notes ? $loadPlan->loading_notes . "\n" : '') . 
                "Cancelled: " . $request->cancellation_reason;
            $loadPlan->save();
            
            // Update shipment statuses
            $shipmentIds = json_decode($loadPlan->shipment_ids, true);
            Shipment::whereIn('id', $shipmentIds)->update(['shipment_status' => 'planned']);
            
            // Cancel any associated dock schedules
            DockSchedule::where('load_plan_id', $loadPlan->id)
                ->where('appointment_status', '!=', 'completed')
                ->update([
                    'appointment_status' => 'cancelled',
                    'special_instructions' => DB::raw("CONCAT(IFNULL(special_instructions, ''), '\nCancelled due to load plan cancellation')")
                ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Load plan cancelled successfully',
                'data' => $loadPlan
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel load plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get loading docks
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
        
        // Filter by type (only show outbound docks by default)
        $query->where('dock_type', $request->dock_type ?? 'outbound');
        
        $loadingDocks = $query->orderBy('dock_name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $loadingDocks
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
     * Get dock schedules
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
     * Schedule dock appointment
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function scheduleDockAppointment(Request $request)
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
                    ->orWhereBetween('scheduled_end_time', [$request->scheduled_start_time, $request->scheduled_end_time]);
            })
            ->where('appointment_status', '!=', 'cancelled')
            ->count();
        
        if ($conflictingAppointments > 0) {
            return response()->json([
                'success' => false,
                'message' => 'The selected dock is not available for the requested time'
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
            'message' => 'Dock appointment scheduled successfully',
            'data' => $dockSchedule
        ], 201);
    }

    /**
     * Update dock appointment
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateDockAppointment(Request $request, $id)
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
                        ->orWhereBetween('scheduled_end_time', [$startTime, $endTime]);
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
        if ($request->appointment_status === 'in_progress' && $dockSchedule->appointment_status !== 'in_progress') {
            $request->merge(['actual_start_time' => now()->format('H:i:s')]);
        }
        
        // If status is changing to completed, set actual end time
        if ($request->appointment_status === 'completed' && $dockSchedule->appointment_status !== 'completed') {
            $request->merge(['actual_end_time' => now()->format('H:i:s')]);
        }
        
        $dockSchedule->update($request->all());
        
        // If status is changing to completed, update loading dock status
        if ($request->appointment_status === 'completed' && $dockSchedule->appointment_status !== 'completed') {
            $loadingDock = LoadingDock::find($dockSchedule->loading_dock_id);
            $loadingDock->dock_status = 'available';
            $loadingDock->save();
        }
        
        // If status is changing to in_progress, update loading dock status
        if ($request->appointment_status === 'in_progress' && $dockSchedule->appointment_status !== 'in_progress') {
            $loadingDock = LoadingDock::find($dockSchedule->loading_dock_id);
            $loadingDock->dock_status = 'occupied';
            $loadingDock->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Dock appointment updated successfully',
            'data' => $dockSchedule
        ]);
    }

    /**
     * Cancel dock appointment
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function cancelDockAppointment(Request $request, $id)
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
            'message' => 'Dock appointment cancelled successfully',
            'data' => $dockSchedule
        ]);
    }

    /**
     * Confirm loading
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function confirmLoading(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'load_plan_id' => 'required|exists:load_plans,id',
            'dock_schedule_id' => 'required|exists:dock_schedules,id',
            'loaded_shipments' => 'required|json',
            'actual_weight_kg' => 'nullable|numeric',
            'total_pieces' => 'required|integer',
            'loading_method' => 'required|in:manual,forklift,conveyor,automated',
            'loading_supervisor_id' => 'required|exists:employees,id',
            'driver_signature' => 'nullable|string',
            'loading_photos' => 'nullable|json',
            'loading_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $loadingConfirmation = LoadingConfirmation::create([
                'load_plan_id' => $request->load_plan_id,
                'dock_schedule_id' => $request->dock_schedule_id,
                'loaded_shipments' => $request->loaded_shipments,
                'actual_weight_kg' => $request->actual_weight_kg,
                'total_pieces' => $request->total_pieces,
                'loading_method' => $request->loading_method,
                'loading_supervisor_id' => $request->loading_supervisor_id,
                'driver_signature' => $request->driver_signature,
                'loading_photos' => $request->loading_photos,
                'loading_notes' => $request->loading_notes,
                'loading_started_at' => now()->subHours(1), // Assuming loading started 1 hour ago
                'loading_completed_at' => now()
            ]);
            
            // Update load plan status
            $loadPlan = LoadPlan::find($request->load_plan_id);
            $loadPlan->load_status = 'loaded';
            $loadPlan->actual_departure_time = now();
            $loadPlan->save();
            
            // Update dock schedule status
            $dockSchedule = DockSchedule::find($request->dock_schedule_id);
            $dockSchedule->appointment_status = 'completed';
            $dockSchedule->actual_start_time = $dockSchedule->actual_start_time ?? now()->subHours(1)->format('H:i:s');
            $dockSchedule->actual_end_time = now()->format('H:i:s');
            $dockSchedule->save();
            
            // Update loading dock status
            $loadingDock = LoadingDock::find($dockSchedule->loading_dock_id);
            $loadingDock->dock_status = 'available';
            $loadingDock->save();
            
            // Update shipment statuses
            $shipmentIds = json_decode($request->loaded_shipments, true);
            Shipment::whereIn('id', $shipmentIds)->update([
                'shipment_status' => 'picked_up'
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Loading confirmed successfully',
                'data' => $loadingConfirmation
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm loading',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dock utilization metrics
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getDockUtilizationMetrics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get all outbound docks for the warehouse
        $docks = LoadingDock::where('warehouse_id', $request->warehouse_id)
            ->where('dock_type', 'outbound')
            ->get();
        
        $dockIds = $docks->pluck('id')->toArray();
        
        // Get all appointments in date range
        $appointments = DockSchedule::whereIn('loading_dock_id', $dockIds)
            ->whereBetween('scheduled_date', [$request->from_date, $request->to_date])
            ->get();
        
        // Calculate metrics
        $totalAppointments = $appointments->count();
        $completedAppointments = $appointments->where('appointment_status', 'completed')->count();
        $cancelledAppointments = $appointments->where('appointment_status', 'cancelled')->count();
        $noShowAppointments = $appointments->where('appointment_status', 'no_show')->count();
        
        $completionRate = $totalAppointments > 0 ? ($completedAppointments / $totalAppointments) * 100 : 0;
        $cancellationRate = $totalAppointments > 0 ? ($cancelledAppointments / $totalAppointments) * 100 : 0;
        $noShowRate = $totalAppointments > 0 ? ($noShowAppointments / $totalAppointments) * 100 : 0;
        
        // Calculate average loading time
        $avgLoadingTimeMinutes = 0;
        $completedWithTimes = $appointments->where('appointment_status', 'completed')
            ->whereNotNull('actual_start_time')
            ->whereNotNull('actual_end_time');
        
        if ($completedWithTimes->count() > 0) {
            $totalMinutes = 0;
            
            foreach ($completedWithTimes as $appointment) {
                $startTime = strtotime($appointment->actual_start_time);
                $endTime = strtotime($appointment->actual_end_time);
                
                // Handle case where end time is earlier than start time (next day)
                if ($endTime < $startTime) {
                    $endTime += 24 * 60 * 60; // Add 24 hours
                }
                
                $totalMinutes += ($endTime - $startTime) / 60;
            }
            
            $avgLoadingTimeMinutes = $totalMinutes / $completedWithTimes->count();
        }
        
        // Calculate dock utilization by day
        $dateRange = new \DatePeriod(
            new \DateTime($request->from_date),
            new \DateInterval('P1D'),
            new \DateTime($request->to_date . ' +1 day')
        );
        
        $dailyUtilization = [];
        $totalDockHours = count($dockIds) * 24; // Assuming 24-hour availability
        
        foreach ($dateRange as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayAppointments = $appointments->where('scheduled_date', $dateStr)
                ->where('appointment_status', '!=', 'cancelled');
            
            $totalHoursUsed = 0;
            
            foreach ($dayAppointments as $appointment) {
                $startTime = strtotime($appointment->scheduled_start_time);
                $endTime = strtotime($appointment->scheduled_end_time);
                
                // Handle case where end time is earlier than start time (next day)
                if ($endTime < $startTime) {
                    $endTime += 24 * 60 * 60; // Add 24 hours
                }
                
                $totalHoursUsed += ($endTime - $startTime) / 3600;
            }
            
            $utilizationPercentage = ($totalHoursUsed / $totalDockHours) * 100;
            
            $dailyUtilization[] = [
                'date' => $dateStr,
                'utilization_percentage' => round($utilizationPercentage, 2),
                'hours_used' => round($totalHoursUsed, 2),
                'total_appointments' => $dayAppointments->count()
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_docks' => count($dockIds),
                    'total_appointments' => $totalAppointments,
                    'completed_appointments' => $completedAppointments,
                    'cancelled_appointments' => $cancelledAppointments,
                    'no_show_appointments' => $noShowAppointments,
                    'completion_rate' => round($completionRate, 2),
                    'cancellation_rate' => round($cancellationRate, 2),
                    'no_show_rate' => round($noShowRate, 2),
                    'avg_loading_time_minutes' => round($avgLoadingTimeMinutes, 2)
                ],
                'daily_utilization' => $dailyUtilization
            ]
        ]);
    }
}