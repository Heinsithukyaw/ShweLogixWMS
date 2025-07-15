<?php

namespace App\Http\Controllers\Batch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Batch\BatchJobDefinition;
use App\Models\Batch\BatchJobSchedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BatchJobScheduleController extends Controller
{
    /**
     * Display a listing of batch job schedules.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = BatchJobSchedule::with('jobDefinition');
            
            // Apply filters
            if ($request->has('job_definition_id')) {
                $query->where('job_definition_id', $request->job_definition_id);
            }
            
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            if ($request->has('schedule_type')) {
                $query->where('schedule_type', $request->schedule_type);
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $schedules = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $schedules,
                'message' => 'Batch job schedules retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving batch job schedules: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve batch job schedules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created batch job schedule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'job_definition_id' => 'required|exists:batch_job_definitions,id',
                'schedule_name' => 'required|string|max:255',
                'schedule_type' => 'required|string|in:cron,interval,daily,weekly,monthly',
                'schedule_configuration' => 'required|json',
                'job_parameters' => 'nullable|json',
                'next_run_at' => 'nullable|date',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if job definition exists and is active
            $jobDefinition = BatchJobDefinition::findOrFail($request->job_definition_id);
            
            if (!$jobDefinition->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot create schedule for inactive job definition'
                ], 422);
            }
            
            // Validate schedule configuration based on schedule type
            $scheduleConfig = json_decode($request->schedule_configuration, true);
            $validationError = $this->validateScheduleConfiguration($request->schedule_type, $scheduleConfig);
            
            if ($validationError) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid schedule configuration',
                    'errors' => ['schedule_configuration' => [$validationError]]
                ], 422);
            }
            
            // Calculate next run time if not provided
            if (!$request->has('next_run_at')) {
                $nextRunAt = $this->calculateNextRunTime($request->schedule_type, $scheduleConfig);
                $request->merge(['next_run_at' => $nextRunAt]);
            }
            
            $schedule = BatchJobSchedule::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $schedule->load('jobDefinition'),
                'message' => 'Batch job schedule created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating batch job schedule: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create batch job schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified batch job schedule.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $schedule = BatchJobSchedule::with(['jobDefinition', 'instances'])
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $schedule,
                'message' => 'Batch job schedule retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving batch job schedule: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Batch job schedule not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified batch job schedule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $schedule = BatchJobSchedule::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'job_definition_id' => 'exists:batch_job_definitions,id',
                'schedule_name' => 'string|max:255',
                'schedule_type' => 'string|in:cron,interval,daily,weekly,monthly',
                'schedule_configuration' => 'json',
                'job_parameters' => 'nullable|json',
                'next_run_at' => 'nullable|date',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if job definition is active if changing
            if ($request->has('job_definition_id') && $request->job_definition_id != $schedule->job_definition_id) {
                $jobDefinition = BatchJobDefinition::findOrFail($request->job_definition_id);
                
                if (!$jobDefinition->is_active) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot assign schedule to inactive job definition'
                    ], 422);
                }
            }
            
            // Validate schedule configuration if changing
            if ($request->has('schedule_type') || $request->has('schedule_configuration')) {
                $scheduleType = $request->schedule_type ?? $schedule->schedule_type;
                $scheduleConfig = $request->has('schedule_configuration') 
                    ? json_decode($request->schedule_configuration, true)
                    : $schedule->schedule_configuration;
                
                $validationError = $this->validateScheduleConfiguration($scheduleType, $scheduleConfig);
                
                if ($validationError) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid schedule configuration',
                        'errors' => ['schedule_configuration' => [$validationError]]
                    ], 422);
                }
                
                // Recalculate next run time if schedule changed and not explicitly provided
                if (!$request->has('next_run_at')) {
                    $nextRunAt = $this->calculateNextRunTime($scheduleType, $scheduleConfig);
                    $request->merge(['next_run_at' => $nextRunAt]);
                }
            }
            
            $schedule->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $schedule->load('jobDefinition'),
                'message' => 'Batch job schedule updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating batch job schedule: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update batch job schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified batch job schedule.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $schedule = BatchJobSchedule::findOrFail($id);
            
            $schedule->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Batch job schedule deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting batch job schedule: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete batch job schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the active status of a batch job schedule.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleActive($id)
    {
        try {
            $schedule = BatchJobSchedule::findOrFail($id);
            
            $schedule->update([
                'is_active' => !$schedule->is_active
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $schedule->load('jobDefinition'),
                'message' => 'Batch job schedule ' . ($schedule->is_active ? 'activated' : 'deactivated') . ' successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling batch job schedule status: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle schedule status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run a scheduled job immediately.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function runNow($id)
    {
        try {
            $schedule = BatchJobSchedule::with('jobDefinition')->findOrFail($id);
            
            // Check if job definition is active
            if (!$schedule->jobDefinition->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot run schedule with inactive job definition'
                ], 422);
            }
            
            // Dispatch job
            // This is a placeholder - actual implementation would use Laravel's job dispatching
            $jobInstance = $this->dispatchScheduledJob($schedule);
            
            return response()->json([
                'status' => 'success',
                'data' => $jobInstance,
                'message' => 'Scheduled job dispatched successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error running scheduled job: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to run scheduled job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate schedule configuration based on schedule type.
     *
     * @param  string  $scheduleType
     * @param  array  $scheduleConfig
     * @return string|null
     */
    private function validateScheduleConfiguration($scheduleType, $scheduleConfig)
    {
        switch ($scheduleType) {
            case 'cron':
                if (!isset($scheduleConfig['expression'])) {
                    return 'Cron expression is required';
                }
                // Validate cron expression
                break;
                
            case 'interval':
                if (!isset($scheduleConfig['interval']) || !isset($scheduleConfig['unit'])) {
                    return 'Interval and unit are required';
                }
                if (!is_numeric($scheduleConfig['interval']) || $scheduleConfig['interval'] <= 0) {
                    return 'Interval must be a positive number';
                }
                if (!in_array($scheduleConfig['unit'], ['minutes', 'hours', 'days'])) {
                    return 'Unit must be one of: minutes, hours, days';
                }
                break;
                
            case 'daily':
                if (!isset($scheduleConfig['time'])) {
                    return 'Time is required for daily schedule';
                }
                // Validate time format (HH:MM)
                break;
                
            case 'weekly':
                if (!isset($scheduleConfig['day']) || !isset($scheduleConfig['time'])) {
                    return 'Day and time are required for weekly schedule';
                }
                if (!in_array($scheduleConfig['day'], range(0, 6))) {
                    return 'Day must be between 0 (Sunday) and 6 (Saturday)';
                }
                // Validate time format (HH:MM)
                break;
                
            case 'monthly':
                if (!isset($scheduleConfig['day']) || !isset($scheduleConfig['time'])) {
                    return 'Day and time are required for monthly schedule';
                }
                if (!in_array($scheduleConfig['day'], range(1, 31))) {
                    return 'Day must be between 1 and 31';
                }
                // Validate time format (HH:MM)
                break;
                
            default:
                return 'Invalid schedule type';
        }
        
        return null;
    }

    /**
     * Calculate the next run time based on schedule configuration.
     *
     * @param  string  $scheduleType
     * @param  array  $scheduleConfig
     * @return \Carbon\Carbon
     */
    private function calculateNextRunTime($scheduleType, $scheduleConfig)
    {
        $now = now();
        
        switch ($scheduleType) {
            case 'cron':
                // This is a placeholder - actual implementation would use cron expression parser
                return $now->addHour();
                
            case 'interval':
                $interval = $scheduleConfig['interval'];
                $unit = $scheduleConfig['unit'];
                
                switch ($unit) {
                    case 'minutes':
                        return $now->addMinutes($interval);
                    case 'hours':
                        return $now->addHours($interval);
                    case 'days':
                        return $now->addDays($interval);
                    default:
                        return $now->addHour();
                }
                
            case 'daily':
                $time = $scheduleConfig['time'];
                list($hour, $minute) = explode(':', $time);
                
                $nextRun = $now->copy()->setTime($hour, $minute, 0);
                
                if ($nextRun->isPast()) {
                    $nextRun->addDay();
                }
                
                return $nextRun;
                
            case 'weekly':
                $day = $scheduleConfig['day'];
                $time = $scheduleConfig['time'];
                list($hour, $minute) = explode(':', $time);
                
                $nextRun = $now->copy()->setTime($hour, $minute, 0);
                
                while ($nextRun->dayOfWeek != $day) {
                    $nextRun->addDay();
                }
                
                if ($nextRun->isPast()) {
                    $nextRun->addWeek();
                }
                
                return $nextRun;
                
            case 'monthly':
                $day = $scheduleConfig['day'];
                $time = $scheduleConfig['time'];
                list($hour, $minute) = explode(':', $time);
                
                $nextRun = $now->copy()->setDay(1)->setTime($hour, $minute, 0);
                
                // Adjust to the specified day of month
                $daysInMonth = $nextRun->daysInMonth;
                $targetDay = min($day, $daysInMonth);
                $nextRun->setDay($targetDay);
                
                if ($nextRun->isPast()) {
                    $nextRun->addMonth();
                    $daysInMonth = $nextRun->daysInMonth;
                    $targetDay = min($day, $daysInMonth);
                    $nextRun->setDay($targetDay);
                }
                
                return $nextRun;
                
            default:
                return $now->addHour();
        }
    }

    /**
     * Dispatch a scheduled job.
     *
     * @param  \App\Models\Batch\BatchJobSchedule  $schedule
     * @return \App\Models\Batch\BatchJobInstance
     */
    private function dispatchScheduledJob($schedule)
    {
        // This is a placeholder - actual implementation would use Laravel's job dispatching
        $jobInstance = new \App\Models\Batch\BatchJobInstance([
            'job_definition_id' => $schedule->job_definition_id,
            'schedule_id' => $schedule->id,
            'status' => 'queued',
            'job_parameters' => $schedule->job_parameters,
            'total_records' => 0,
            'processed_records' => 0,
            'success_records' => 0,
            'error_records' => 0,
        ]);
        
        $jobInstance->save();
        
        // In a real implementation, you would dispatch the job to the queue
        // For example: ProcessBatchJob::dispatch($jobInstance);
        
        return $jobInstance;
    }
}