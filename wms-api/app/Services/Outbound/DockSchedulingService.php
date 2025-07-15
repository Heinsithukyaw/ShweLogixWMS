<?php

namespace App\Services\Outbound;

use App\Models\Outbound\LoadingDock;
use App\Models\Outbound\DockSchedule;
use App\Models\Outbound\LoadPlan;
use App\Models\ShippingCarrier;
use Exception;
use DB;

class DockSchedulingService
{
    /**
     * Create a new loading dock
     *
     * @param array $data
     * @return \App\Models\Outbound\LoadingDock
     */
    public function createLoadingDock(array $data)
    {
        // Generate dock code if not provided
        if (!isset($data['dock_code'])) {
            $data['dock_code'] = $this->generateDockCode($data['warehouse_id']);
        }
        
        // Create the loading dock
        $loadingDock = LoadingDock::create($data);
        
        return $loadingDock;
    }
    
    /**
     * Generate a unique dock code
     *
     * @param int $warehouseId
     * @return string
     */
    private function generateDockCode($warehouseId)
    {
        $prefix = 'D' . $warehouseId;
        $count = LoadingDock::where('warehouse_id', $warehouseId)->count() + 1;
        
        return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create a new dock schedule
     *
     * @param array $data
     * @return \App\Models\Outbound\DockSchedule
     */
    public function createDockSchedule(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Check if the dock is available for the requested time slot
            $this->checkDockAvailability(
                $data['loading_dock_id'],
                $data['scheduled_date'],
                $data['scheduled_start_time'],
                $data['scheduled_end_time']
            );
            
            // Create the dock schedule
            $dockSchedule = DockSchedule::create($data);
            
            // Update load plan if provided
            if (isset($data['load_plan_id'])) {
                $loadPlan = LoadPlan::findOrFail($data['load_plan_id']);
                $loadPlan->update([
                    'planned_departure_date' => $data['scheduled_date'],
                    'planned_departure_time' => $data['scheduled_end_time']
                ]);
            }
            
            DB::commit();
            return $dockSchedule;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Check if a dock is available for a specific time slot
     *
     * @param int $dockId
     * @param string $date
     * @param string $startTime
     * @param string $endTime
     * @return bool
     * @throws \Exception
     */
    private function checkDockAvailability($dockId, $date, $startTime, $endTime)
    {
        // Check if the dock exists and is active
        $dock = LoadingDock::findOrFail($dockId);
        
        if ($dock->dock_status !== 'available') {
            throw new Exception('Loading dock is not available');
        }
        
        // Check for overlapping schedules
        $overlappingSchedules = DockSchedule::where('loading_dock_id', $dockId)
            ->where('scheduled_date', $date)
            ->where('appointment_status', '!=', 'cancelled')
            ->where(function($query) use ($startTime, $endTime) {
                $query->where(function($q) use ($startTime, $endTime) {
                    // New schedule starts during an existing schedule
                    $q->where('scheduled_start_time', '<=', $startTime)
                      ->where('scheduled_end_time', '>', $startTime);
                })->orWhere(function($q) use ($startTime, $endTime) {
                    // New schedule ends during an existing schedule
                    $q->where('scheduled_start_time', '<', $endTime)
                      ->where('scheduled_end_time', '>=', $endTime);
                })->orWhere(function($q) use ($startTime, $endTime) {
                    // New schedule completely contains an existing schedule
                    $q->where('scheduled_start_time', '>=', $startTime)
                      ->where('scheduled_end_time', '<=', $endTime);
                });
            })
            ->count();
        
        if ($overlappingSchedules > 0) {
            throw new Exception('The requested time slot is not available');
        }
        
        return true;
    }
    
    /**
     * Update a dock schedule
     *
     * @param int $scheduleId
     * @param array $data
     * @return \App\Models\Outbound\DockSchedule
     */
    public function updateDockSchedule($scheduleId, array $data)
    {
        DB::beginTransaction();
        
        try {
            $dockSchedule = DockSchedule::findOrFail($scheduleId);
            
            // Check if the schedule can be updated
            if (in_array($dockSchedule->appointment_status, ['completed', 'cancelled'])) {
                throw new Exception('Cannot update a completed or cancelled schedule');
            }
            
            // Check availability if changing time or dock
            if ((isset($data['loading_dock_id']) && $data['loading_dock_id'] != $dockSchedule->loading_dock_id) ||
                (isset($data['scheduled_date']) && $data['scheduled_date'] != $dockSchedule->scheduled_date) ||
                (isset($data['scheduled_start_time']) && $data['scheduled_start_time'] != $dockSchedule->scheduled_start_time) ||
                (isset($data['scheduled_end_time']) && $data['scheduled_end_time'] != $dockSchedule->scheduled_end_time)) {
                
                $this->checkDockAvailability(
                    $data['loading_dock_id'] ?? $dockSchedule->loading_dock_id,
                    $data['scheduled_date'] ?? $dockSchedule->scheduled_date,
                    $data['scheduled_start_time'] ?? $dockSchedule->scheduled_start_time,
                    $data['scheduled_end_time'] ?? $dockSchedule->scheduled_end_time
                );
            }
            
            // Update the dock schedule
            $dockSchedule->update($data);
            
            // Update load plan if needed
            if ($dockSchedule->load_plan_id && 
                (isset($data['scheduled_date']) || isset($data['scheduled_end_time']))) {
                
                $loadPlan = LoadPlan::findOrFail($dockSchedule->load_plan_id);
                $loadPlan->update([
                    'planned_departure_date' => $data['scheduled_date'] ?? $dockSchedule->scheduled_date,
                    'planned_departure_time' => $data['scheduled_end_time'] ?? $dockSchedule->scheduled_end_time
                ]);
            }
            
            DB::commit();
            return $dockSchedule;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Confirm a dock schedule
     *
     * @param int $scheduleId
     * @param array $data
     * @return \App\Models\Outbound\DockSchedule
     */
    public function confirmDockSchedule($scheduleId, array $data)
    {
        $dockSchedule = DockSchedule::findOrFail($scheduleId);
        
        if ($dockSchedule->appointment_status !== 'scheduled') {
            throw new Exception('Only scheduled appointments can be confirmed');
        }
        
        // Update the dock schedule
        $dockSchedule->update([
            'appointment_status' => 'confirmed',
            'driver_name' => $data['driver_name'] ?? $dockSchedule->driver_name,
            'vehicle_license' => $data['vehicle_license'] ?? $dockSchedule->vehicle_license,
            'trailer_number' => $data['trailer_number'] ?? $dockSchedule->trailer_number
        ]);
        
        return $dockSchedule;
    }
    
    /**
     * Start a dock schedule
     *
     * @param int $scheduleId
     * @return \App\Models\Outbound\DockSchedule
     */
    public function startDockSchedule($scheduleId)
    {
        $dockSchedule = DockSchedule::findOrFail($scheduleId);
        
        if (!in_array($dockSchedule->appointment_status, ['scheduled', 'confirmed'])) {
            throw new Exception('Only scheduled or confirmed appointments can be started');
        }
        
        // Update the dock schedule
        $dockSchedule->update([
            'appointment_status' => 'in_progress',
            'actual_start_time' => now()->format('H:i:s')
        ]);
        
        // Update loading dock status
        $loadingDock = LoadingDock::findOrFail($dockSchedule->loading_dock_id);
        $loadingDock->update([
            'dock_status' => 'occupied'
        ]);
        
        return $dockSchedule;
    }
    
    /**
     * Complete a dock schedule
     *
     * @param int $scheduleId
     * @return \App\Models\Outbound\DockSchedule
     */
    public function completeDockSchedule($scheduleId)
    {
        DB::beginTransaction();
        
        try {
            $dockSchedule = DockSchedule::findOrFail($scheduleId);
            
            if ($dockSchedule->appointment_status !== 'in_progress') {
                throw new Exception('Only in-progress appointments can be completed');
            }
            
            // Update the dock schedule
            $dockSchedule->update([
                'appointment_status' => 'completed',
                'actual_end_time' => now()->format('H:i:s')
            ]);
            
            // Update loading dock status
            $loadingDock = LoadingDock::findOrFail($dockSchedule->loading_dock_id);
            $loadingDock->update([
                'dock_status' => 'available'
            ]);
            
            DB::commit();
            return $dockSchedule;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Cancel a dock schedule
     *
     * @param int $scheduleId
     * @param string $reason
     * @return \App\Models\Outbound\DockSchedule
     */
    public function cancelDockSchedule($scheduleId, $reason = null)
    {
        $dockSchedule = DockSchedule::findOrFail($scheduleId);
        
        if (in_array($dockSchedule->appointment_status, ['completed', 'cancelled'])) {
            throw new Exception('Cannot cancel a completed or already cancelled schedule');
        }
        
        // Update the dock schedule
        $dockSchedule->update([
            'appointment_status' => 'cancelled',
            'special_instructions' => $reason ? ($dockSchedule->special_instructions . "\nCancellation reason: " . $reason) : $dockSchedule->special_instructions
        ]);
        
        return $dockSchedule;
    }
    
    /**
     * Mark a dock schedule as no-show
     *
     * @param int $scheduleId
     * @return \App\Models\Outbound\DockSchedule
     */
    public function markNoShow($scheduleId)
    {
        $dockSchedule = DockSchedule::findOrFail($scheduleId);
        
        if (!in_array($dockSchedule->appointment_status, ['scheduled', 'confirmed'])) {
            throw new Exception('Only scheduled or confirmed appointments can be marked as no-show');
        }
        
        // Update the dock schedule
        $dockSchedule->update([
            'appointment_status' => 'no_show'
        ]);
        
        return $dockSchedule;
    }
    
    /**
     * Get dock schedule calendar
     *
     * @param int $warehouseId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDockScheduleCalendar($warehouseId, $startDate, $endDate)
    {
        // Get all loading docks for the warehouse
        $docks = LoadingDock::where('warehouse_id', $warehouseId)->get();
        
        // Get all schedules for the specified period
        $schedules = DockSchedule::whereIn('loading_dock_id', $docks->pluck('id'))
            ->whereDate('scheduled_date', '>=', $startDate)
            ->whereDate('scheduled_date', '<=', $endDate)
            ->with(['carrier', 'loadPlan'])
            ->get();
        
        // Format the calendar data
        $calendar = [];
        
        foreach ($docks as $dock) {
            $dockSchedules = $schedules->where('loading_dock_id', $dock->id);
            $events = [];
            
            foreach ($dockSchedules as $schedule) {
                $events[] = [
                    'id' => $schedule->id,
                    'title' => $schedule->carrier ? $schedule->carrier->carrier_name : 'Appointment',
                    'start' => $schedule->scheduled_date . 'T' . $schedule->scheduled_start_time,
                    'end' => $schedule->scheduled_date . 'T' . $schedule->scheduled_end_time,
                    'status' => $schedule->appointment_status,
                    'load_plan_id' => $schedule->load_plan_id,
                    'load_plan_number' => $schedule->loadPlan ? $schedule->loadPlan->load_plan_number : null,
                    'driver_name' => $schedule->driver_name,
                    'vehicle_license' => $schedule->vehicle_license,
                    'trailer_number' => $schedule->trailer_number,
                    'special_instructions' => $schedule->special_instructions
                ];
            }
            
            $calendar[] = [
                'dock_id' => $dock->id,
                'dock_code' => $dock->dock_code,
                'dock_name' => $dock->dock_name,
                'dock_type' => $dock->dock_type,
                'dock_status' => $dock->dock_status,
                'events' => $events
            ];
        }
        
        return $calendar;
    }
    
    /**
     * Get dock availability
     *
     * @param int $warehouseId
     * @param string $date
     * @return array
     */
    public function getDockAvailability($warehouseId, $date)
    {
        // Get all loading docks for the warehouse
        $docks = LoadingDock::where('warehouse_id', $warehouseId)->get();
        
        // Get all schedules for the specified date
        $schedules = DockSchedule::whereIn('loading_dock_id', $docks->pluck('id'))
            ->where('scheduled_date', $date)
            ->where('appointment_status', '!=', 'cancelled')
            ->get();
        
        // Define time slots (hourly from 00:00 to 23:00)
        $timeSlots = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $timeSlots[] = sprintf('%02d:00', $hour);
        }
        
        // Format the availability data
        $availability = [];
        
        foreach ($docks as $dock) {
            $dockSchedules = $schedules->where('loading_dock_id', $dock->id);
            $slotAvailability = [];
            
            foreach ($timeSlots as $slot) {
                $slotStart = strtotime($slot);
                $slotEnd = strtotime('+1 hour', $slotStart);
                $isAvailable = true;
                $conflictingSchedule = null;
                
                foreach ($dockSchedules as $schedule) {
                    $scheduleStart = strtotime($schedule->scheduled_start_time);
                    $scheduleEnd = strtotime($schedule->scheduled_end_time);
                    
                    // Check if the schedule overlaps with the time slot
                    if (($scheduleStart < $slotEnd) && ($scheduleEnd > $slotStart)) {
                        $isAvailable = false;
                        $conflictingSchedule = $schedule;
                        break;
                    }
                }
                
                $slotAvailability[] = [
                    'time_slot' => $slot,
                    'is_available' => $isAvailable && $dock->dock_status === 'available',
                    'schedule_id' => $conflictingSchedule ? $conflictingSchedule->id : null,
                    'carrier_name' => $conflictingSchedule && $conflictingSchedule->carrier ? $conflictingSchedule->carrier->carrier_name : null,
                    'appointment_status' => $conflictingSchedule ? $conflictingSchedule->appointment_status : null
                ];
            }
            
            $availability[] = [
                'dock_id' => $dock->id,
                'dock_code' => $dock->dock_code,
                'dock_name' => $dock->dock_name,
                'dock_type' => $dock->dock_type,
                'dock_status' => $dock->dock_status,
                'time_slots' => $slotAvailability
            ];
        }
        
        return $availability;
    }
}