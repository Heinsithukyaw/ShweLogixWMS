<?php

namespace App\Services\Outbound;

use App\Models\Outbound\LoadPlan;
use App\Models\Outbound\LoadingDock;
use App\Models\Outbound\DockSchedule;
use App\Models\Outbound\LoadingConfirmation;
use App\Models\Outbound\Shipment;
use App\Models\ShippingCarrier;
use App\Models\Employee;
use Exception;
use DB;

class LoadPlanningService
{
    /**
     * Create a new load plan
     *
     * @param array $data
     * @return \App\Models\Outbound\LoadPlan
     */
    public function createLoadPlan(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Generate load plan number if not provided
            if (!isset($data['load_plan_number'])) {
                $data['load_plan_number'] = $this->generateLoadPlanNumber();
            }
            
            // Calculate utilization if not provided
            if (isset($data['total_weight_kg']) && isset($data['vehicle_capacity_weight_kg']) && !isset($data['utilization_weight_pct'])) {
                $data['utilization_weight_pct'] = ($data['total_weight_kg'] / $data['vehicle_capacity_weight_kg']) * 100;
            }
            
            if (isset($data['total_volume_cm3']) && isset($data['vehicle_capacity_volume_cm3']) && !isset($data['utilization_volume_pct'])) {
                $data['utilization_volume_pct'] = ($data['total_volume_cm3'] / $data['vehicle_capacity_volume_cm3']) * 100;
            }
            
            // Create the load plan
            $loadPlan = LoadPlan::create($data);
            
            // Update shipment statuses
            if (isset($data['shipment_ids'])) {
                $shipmentIds = json_decode($data['shipment_ids'], true);
                Shipment::whereIn('id', $shipmentIds)->update([
                    'shipment_status' => 'assigned_to_load'
                ]);
            }
            
            DB::commit();
            return $loadPlan;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Generate a unique load plan number
     *
     * @return string
     */
    private function generateLoadPlanNumber()
    {
        $prefix = 'LP';
        $timestamp = date('YmdHis');
        $random = mt_rand(1000, 9999);
        
        return $prefix . $timestamp . $random;
    }
    
    /**
     * Optimize a load plan
     *
     * @param int $loadPlanId
     * @return \App\Models\Outbound\LoadPlan
     */
    public function optimizeLoadPlan($loadPlanId)
    {
        $loadPlan = LoadPlan::findOrFail($loadPlanId);
        
        // Get shipments for this load plan
        $shipmentIds = json_decode($loadPlan->shipment_ids, true);
        $shipments = Shipment::whereIn('id', $shipmentIds)->get();
        
        // Sort shipments by delivery address proximity
        // This would require a more complex implementation with geocoding and routing algorithms
        // For now, we'll just sort by expected delivery date
        $sortedShipments = $shipments->sortBy('expected_delivery_date')->values();
        
        // Generate loading sequence
        $loadingSequence = [];
        foreach ($sortedShipments as $index => $shipment) {
            $loadingSequence[] = [
                'sequence_number' => $index + 1,
                'shipment_id' => $shipment->id,
                'shipment_number' => $shipment->shipment_number,
                'total_cartons' => $shipment->total_cartons,
                'total_weight_kg' => $shipment->total_weight_kg,
                'total_volume_cm3' => $shipment->total_volume_cm3,
                'expected_delivery_date' => $shipment->expected_delivery_date
            ];
        }
        
        // Update the load plan
        $loadPlan->update([
            'loading_sequence' => json_encode($loadingSequence)
        ]);
        
        return $loadPlan;
    }
    
    /**
     * Confirm loading of a load plan
     *
     * @param array $data
     * @return \App\Models\Outbound\LoadingConfirmation
     */
    public function confirmLoading(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Create loading confirmation
            $loadingConfirmation = LoadingConfirmation::create($data);
            
            // Update load plan status
            $loadPlan = LoadPlan::findOrFail($data['load_plan_id']);
            $loadPlan->update([
                'load_status' => 'loaded',
                'actual_departure_time' => $data['loading_completed_at'] ?? now()
            ]);
            
            // Update shipment statuses
            $shipmentIds = json_decode($data['loaded_shipments'], true);
            Shipment::whereIn('id', $shipmentIds)->update([
                'shipment_status' => 'picked_up'
            ]);
            
            // Update dock schedule if provided
            if (isset($data['dock_schedule_id'])) {
                $dockSchedule = DockSchedule::findOrFail($data['dock_schedule_id']);
                $dockSchedule->update([
                    'appointment_status' => 'completed',
                    'actual_end_time' => $data['loading_completed_at'] ?? now()
                ]);
            }
            
            DB::commit();
            return $loadingConfirmation;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Dispatch a load plan
     *
     * @param int $loadPlanId
     * @return \App\Models\Outbound\LoadPlan
     */
    public function dispatchLoadPlan($loadPlanId)
    {
        DB::beginTransaction();
        
        try {
            $loadPlan = LoadPlan::findOrFail($loadPlanId);
            
            if ($loadPlan->load_status !== 'loaded') {
                throw new Exception('Load plan must be in loaded status to dispatch');
            }
            
            // Update load plan status
            $loadPlan->update([
                'load_status' => 'dispatched',
                'actual_departure_time' => now()
            ]);
            
            // Update shipment statuses
            $shipmentIds = json_decode($loadPlan->shipment_ids, true);
            Shipment::whereIn('id', $shipmentIds)->update([
                'shipment_status' => 'in_transit'
            ]);
            
            DB::commit();
            return $loadPlan;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Cancel a load plan
     *
     * @param int $loadPlanId
     * @return \App\Models\Outbound\LoadPlan
     */
    public function cancelLoadPlan($loadPlanId)
    {
        DB::beginTransaction();
        
        try {
            $loadPlan = LoadPlan::findOrFail($loadPlanId);
            
            if (in_array($loadPlan->load_status, ['loaded', 'dispatched', 'delivered'])) {
                throw new Exception('Cannot cancel a load plan that has been loaded, dispatched, or delivered');
            }
            
            // Update load plan status
            $loadPlan->update([
                'load_status' => 'cancelled'
            ]);
            
            // Update shipment statuses
            $shipmentIds = json_decode($loadPlan->shipment_ids, true);
            Shipment::whereIn('id', $shipmentIds)->update([
                'shipment_status' => 'ready'
            ]);
            
            // Cancel associated dock schedule if exists
            $dockSchedule = DockSchedule::where('load_plan_id', $loadPlanId)->first();
            if ($dockSchedule) {
                $dockSchedule->update([
                    'appointment_status' => 'cancelled'
                ]);
            }
            
            DB::commit();
            return $loadPlan;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Get dock utilization metrics
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDockUtilizationMetrics($startDate = null, $endDate = null)
    {
        // Get all loading docks
        $loadingDocks = LoadingDock::all();
        
        // Get dock schedules for the specified period
        $schedulesQuery = DockSchedule::query();
        
        if ($startDate) {
            $schedulesQuery->whereDate('scheduled_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $schedulesQuery->whereDate('scheduled_date', '<=', $endDate);
        }
        
        $schedules = $schedulesQuery->get();
        
        // Calculate metrics
        $totalDocks = $loadingDocks->count();
        $activeDocks = $loadingDocks->where('dock_status', 'available')->count();
        $totalSchedules = $schedules->count();
        $completedSchedules = $schedules->where('appointment_status', 'completed')->count();
        $cancelledSchedules = $schedules->where('appointment_status', 'cancelled')->count();
        $noShowSchedules = $schedules->where('appointment_status', 'no_show')->count();
        
        // Calculate dock utilization
        $dockUtilization = [];
        foreach ($loadingDocks as $dock) {
            $dockSchedules = $schedules->where('loading_dock_id', $dock->id);
            $totalScheduledMinutes = 0;
            $totalActualMinutes = 0;
            
            foreach ($dockSchedules as $schedule) {
                // Calculate scheduled minutes
                $startTime = strtotime($schedule->scheduled_start_time);
                $endTime = strtotime($schedule->scheduled_end_time);
                $scheduledMinutes = ($endTime - $startTime) / 60;
                $totalScheduledMinutes += $scheduledMinutes;
                
                // Calculate actual minutes for completed schedules
                if ($schedule->appointment_status === 'completed' && $schedule->actual_start_time && $schedule->actual_end_time) {
                    $actualStartTime = strtotime($schedule->actual_start_time);
                    $actualEndTime = strtotime($schedule->actual_end_time);
                    $actualMinutes = ($actualEndTime - $actualStartTime) / 60;
                    $totalActualMinutes += $actualMinutes;
                }
            }
            
            // Calculate utilization percentage
            $totalAvailableMinutes = 0;
            if ($startDate && $endDate) {
                $startDateTime = strtotime($startDate);
                $endDateTime = strtotime($endDate);
                $totalDays = ceil(($endDateTime - $startDateTime) / (60 * 60 * 24));
                $totalAvailableMinutes = $totalDays * 24 * 60; // Assuming 24/7 availability
            } else {
                // Default to 30 days if no date range specified
                $totalAvailableMinutes = 30 * 24 * 60;
            }
            
            $utilizationPercentage = $totalAvailableMinutes > 0 ? ($totalScheduledMinutes / $totalAvailableMinutes) * 100 : 0;
            
            $dockUtilization[] = [
                'dock_id' => $dock->id,
                'dock_code' => $dock->dock_code,
                'dock_name' => $dock->dock_name,
                'total_schedules' => $dockSchedules->count(),
                'completed_schedules' => $dockSchedules->where('appointment_status', 'completed')->count(),
                'total_scheduled_minutes' => $totalScheduledMinutes,
                'total_actual_minutes' => $totalActualMinutes,
                'utilization_percentage' => $utilizationPercentage
            ];
        }
        
        // Sort by utilization percentage (highest first)
        usort($dockUtilization, function($a, $b) {
            return $b['utilization_percentage'] <=> $a['utilization_percentage'];
        });
        
        return [
            'total_docks' => $totalDocks,
            'active_docks' => $activeDocks,
            'total_schedules' => $totalSchedules,
            'completed_schedules' => $completedSchedules,
            'cancelled_schedules' => $cancelledSchedules,
            'no_show_schedules' => $noShowSchedules,
            'completion_rate' => $totalSchedules > 0 ? ($completedSchedules / $totalSchedules) * 100 : 0,
            'cancellation_rate' => $totalSchedules > 0 ? ($cancelledSchedules / $totalSchedules) * 100 : 0,
            'no_show_rate' => $totalSchedules > 0 ? ($noShowSchedules / $totalSchedules) * 100 : 0,
            'dock_utilization' => $dockUtilization
        ];
    }
    
    /**
     * Find available dock slots
     *
     * @param string $date
     * @param int $durationMinutes
     * @param array $criteria
     * @return array
     */
    public function findAvailableDockSlots($date, $durationMinutes, $criteria = [])
    {
        // Get all active loading docks
        $docksQuery = LoadingDock::where('dock_status', 'available');
        
        // Apply criteria filters
        if (isset($criteria['dock_type'])) {
            $docksQuery->where('dock_type', $criteria['dock_type']);
        }
        
        if (isset($criteria['warehouse_id'])) {
            $docksQuery->where('warehouse_id', $criteria['warehouse_id']);
        }
        
        if (isset($criteria['has_dock_leveler'])) {
            $docksQuery->where('has_dock_leveler', $criteria['has_dock_leveler']);
        }
        
        if (isset($criteria['max_vehicle_length_m'])) {
            $docksQuery->where('max_vehicle_length_m', '>=', $criteria['max_vehicle_length_m']);
        }
        
        if (isset($criteria['max_vehicle_height_m'])) {
            $docksQuery->where('max_vehicle_height_m', '>=', $criteria['max_vehicle_height_m']);
        }
        
        $docks = $docksQuery->get();
        
        // Get all schedules for the specified date
        $schedules = DockSchedule::whereDate('scheduled_date', $date)
            ->whereIn('loading_dock_id', $docks->pluck('id'))
            ->where('appointment_status', '!=', 'cancelled')
            ->orderBy('scheduled_start_time')
            ->get();
        
        // Define operating hours (assuming 24/7 operation)
        $operatingStart = '00:00:00';
        $operatingEnd = '23:59:59';
        
        // Find available slots for each dock
        $availableSlots = [];
        
        foreach ($docks as $dock) {
            $dockSchedules = $schedules->where('loading_dock_id', $dock->id);
            $slots = [];
            
            // Start with the full operating day
            $timeSlots = [['start' => $operatingStart, 'end' => $operatingEnd]];
            
            // Remove scheduled times
            foreach ($dockSchedules as $schedule) {
                $newTimeSlots = [];
                
                foreach ($timeSlots as $slot) {
                    // If schedule starts after slot ends or ends before slot starts, keep the slot unchanged
                    if ($schedule->scheduled_start_time >= $slot['end'] || $schedule->scheduled_end_time <= $slot['start']) {
                        $newTimeSlots[] = $slot;
                        continue;
                    }
                    
                    // If schedule starts after slot starts, keep the beginning of the slot
                    if ($schedule->scheduled_start_time > $slot['start']) {
                        $newTimeSlots[] = ['start' => $slot['start'], 'end' => $schedule->scheduled_start_time];
                    }
                    
                    // If schedule ends before slot ends, keep the end of the slot
                    if ($schedule->scheduled_end_time < $slot['end']) {
                        $newTimeSlots[] = ['start' => $schedule->scheduled_end_time, 'end' => $slot['end']];
                    }
                }
                
                $timeSlots = $newTimeSlots;
            }
            
            // Filter slots that are long enough for the requested duration
            foreach ($timeSlots as $slot) {
                $startTime = strtotime($slot['start']);
                $endTime = strtotime($slot['end']);
                $slotDurationMinutes = ($endTime - $startTime) / 60;
                
                if ($slotDurationMinutes >= $durationMinutes) {
                    $slots[] = [
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                        'duration_minutes' => $slotDurationMinutes
                    ];
                }
            }
            
            if (count($slots) > 0) {
                $availableSlots[] = [
                    'dock_id' => $dock->id,
                    'dock_code' => $dock->dock_code,
                    'dock_name' => $dock->dock_name,
                    'warehouse_id' => $dock->warehouse_id,
                    'dock_type' => $dock->dock_type,
                    'available_slots' => $slots
                ];
            }
        }
        
        return $availableSlots;
    }
}