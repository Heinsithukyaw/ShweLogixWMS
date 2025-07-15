<?php

namespace App\Http\Controllers\Batch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Batch\BatchJobDefinition;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BatchJobDefinitionController extends Controller
{
    /**
     * Display a listing of batch job definitions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = BatchJobDefinition::query();
            
            // Apply filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            if ($request->has('job_type')) {
                $query->where('job_type', $request->job_type);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('job_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $jobDefinitions = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $jobDefinitions,
                'message' => 'Batch job definitions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving batch job definitions: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve batch job definitions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created batch job definition.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'job_code' => 'required|string|max:50|unique:batch_job_definitions',
                'description' => 'nullable|string',
                'job_type' => 'required|string|max:50',
                'handler_class' => 'required|string|max:255',
                'job_parameters' => 'nullable|json',
                'chunk_size' => 'nullable|integer',
                'retry_limit' => 'nullable|integer',
                'timeout_minutes' => 'nullable|integer',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $jobDefinition = BatchJobDefinition::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $jobDefinition,
                'message' => 'Batch job definition created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating batch job definition: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create batch job definition',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified batch job definition.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $jobDefinition = BatchJobDefinition::with(['schedules', 'instances'])
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $jobDefinition,
                'message' => 'Batch job definition retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving batch job definition: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Batch job definition not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified batch job definition.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $jobDefinition = BatchJobDefinition::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'job_code' => 'string|max:50|unique:batch_job_definitions,job_code,' . $id,
                'description' => 'nullable|string',
                'job_type' => 'string|max:50',
                'handler_class' => 'string|max:255',
                'job_parameters' => 'nullable|json',
                'chunk_size' => 'nullable|integer',
                'retry_limit' => 'nullable|integer',
                'timeout_minutes' => 'nullable|integer',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $jobDefinition->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $jobDefinition,
                'message' => 'Batch job definition updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating batch job definition: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update batch job definition',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified batch job definition.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $jobDefinition = BatchJobDefinition::findOrFail($id);
            
            // Check if there are related schedules or instances
            if ($jobDefinition->schedules()->count() > 0 || $jobDefinition->instances()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete job definition with associated schedules or instances'
                ], 422);
            }
            
            $jobDefinition->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Batch job definition deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting batch job definition: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete batch job definition',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run a batch job immediately.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function runJob(Request $request, $id)
    {
        try {
            $jobDefinition = BatchJobDefinition::findOrFail($id);
            
            // Check if job is active
            if (!$jobDefinition->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot run inactive job'
                ], 422);
            }
            
            $validator = Validator::make($request->all(), [
                'job_parameters' => 'nullable|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Merge job parameters with definition parameters
            $jobParameters = $jobDefinition->job_parameters ?? [];
            
            if ($request->has('job_parameters')) {
                $jobParameters = array_merge($jobParameters, json_decode($request->job_parameters, true));
            }
            
            // Dispatch job
            // This is a placeholder - actual implementation would use Laravel's job dispatching
            $jobInstance = $this->dispatchJob($jobDefinition, $jobParameters);
            
            return response()->json([
                'status' => 'success',
                'data' => $jobInstance,
                'message' => 'Batch job dispatched successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error running batch job: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to run batch job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dispatch a batch job.
     *
     * @param  \App\Models\Batch\BatchJobDefinition  $jobDefinition
     * @param  array  $jobParameters
     * @return \App\Models\Batch\BatchJobInstance
     */
    private function dispatchJob($jobDefinition, $jobParameters)
    {
        // This is a placeholder - actual implementation would use Laravel's job dispatching
        $jobInstance = new \App\Models\Batch\BatchJobInstance([
            'job_definition_id' => $jobDefinition->id,
            'status' => 'queued',
            'job_parameters' => $jobParameters,
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