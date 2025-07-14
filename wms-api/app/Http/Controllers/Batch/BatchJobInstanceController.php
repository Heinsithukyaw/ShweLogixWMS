<?php

namespace App\Http\Controllers\Batch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Batch\BatchJobInstance;
use App\Models\Batch\BatchJobChunk;
use App\Models\Batch\BatchJobRecord;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BatchJobInstanceController extends Controller
{
    /**
     * Display a listing of batch job instances.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = BatchJobInstance::with(['jobDefinition', 'schedule']);
            
            // Apply filters
            if ($request->has('job_definition_id')) {
                $query->where('job_definition_id', $request->job_definition_id);
            }
            
            if ($request->has('schedule_id')) {
                $query->where('schedule_id', $request->schedule_id);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $instances = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $instances,
                'message' => 'Batch job instances retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving batch job instances: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve batch job instances',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified batch job instance.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $instance = BatchJobInstance::with([
                'jobDefinition',
                'schedule',
                'chunks' => function($query) {
                    $query->orderBy('chunk_number');
                }
            ])->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $instance,
                'message' => 'Batch job instance retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving batch job instance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Batch job instance not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Cancel a running batch job instance.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        try {
            $instance = BatchJobInstance::findOrFail($id);
            
            // Check if job can be cancelled
            if (!in_array($instance->status, ['queued', 'running', 'paused'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel job that is not queued, running, or paused'
                ], 422);
            }
            
            // Update instance status
            $instance->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'error_message' => 'Job cancelled by user'
            ]);
            
            // Update any running chunks
            BatchJobChunk::where('job_instance_id', $id)
                ->whereIn('status', ['queued', 'running'])
                ->update([
                    'status' => 'cancelled',
                    'completed_at' => now(),
                    'error_message' => 'Job cancelled by user'
                ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $instance->load(['jobDefinition', 'schedule']),
                'message' => 'Batch job cancelled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling batch job instance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel batch job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restart a failed or cancelled batch job instance.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restart($id)
    {
        try {
            $instance = BatchJobInstance::findOrFail($id);
            
            // Check if job can be restarted
            if (!in_array($instance->status, ['failed', 'cancelled'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot restart job that is not failed or cancelled'
                ], 422);
            }
            
            // Update instance status
            $instance->update([
                'status' => 'queued',
                'started_at' => null,
                'completed_at' => null,
                'error_message' => null,
                'processed_records' => 0,
                'success_records' => 0,
                'error_records' => 0,
            ]);
            
            // Delete existing chunks and records
            BatchJobRecord::whereIn('chunk_id', function($query) use ($id) {
                $query->select('id')
                    ->from('batch_job_chunks')
                    ->where('job_instance_id', $id);
            })->delete();
            
            BatchJobChunk::where('job_instance_id', $id)->delete();
            
            // Dispatch job
            // This is a placeholder - actual implementation would use Laravel's job dispatching
            // For example: ProcessBatchJob::dispatch($instance);
            
            return response()->json([
                'status' => 'success',
                'data' => $instance->load(['jobDefinition', 'schedule']),
                'message' => 'Batch job restarted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error restarting batch job instance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restart batch job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get chunks for a batch job instance.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getChunks($id)
    {
        try {
            $instance = BatchJobInstance::findOrFail($id);
            
            $chunks = BatchJobChunk::where('job_instance_id', $id)
                ->orderBy('chunk_number')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $chunks,
                'message' => 'Batch job chunks retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving batch job chunks: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve batch job chunks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get records for a batch job chunk.
     *
     * @param  int  $id
     * @param  int  $chunkId
     * @return \Illuminate\Http\Response
     */
    public function getChunkRecords($id, $chunkId)
    {
        try {
            $instance = BatchJobInstance::findOrFail($id);
            
            $chunk = BatchJobChunk::where('job_instance_id', $id)
                ->where('id', $chunkId)
                ->firstOrFail();
            
            $records = BatchJobRecord::where('chunk_id', $chunkId)
                ->orderBy('record_number')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'chunk' => $chunk,
                    'records' => $records
                ],
                'message' => 'Batch job records retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving batch job records: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve batch job records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get error records for a batch job instance.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getErrorRecords($id)
    {
        try {
            $instance = BatchJobInstance::findOrFail($id);
            
            $errorRecords = BatchJobRecord::whereIn('chunk_id', function($query) use ($id) {
                $query->select('id')
                    ->from('batch_job_chunks')
                    ->where('job_instance_id', $id);
            })
            ->where('status', 'error')
            ->orderBy('created_at', 'desc')
            ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $errorRecords,
                'message' => 'Error records retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving batch job error records: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve error records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job execution logs.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getLogs($id)
    {
        try {
            $instance = BatchJobInstance::findOrFail($id);
            
            // This is a placeholder - actual implementation would retrieve logs from storage
            $logs = [
                ['timestamp' => now()->subMinutes(5)->toIso8601String(), 'level' => 'info', 'message' => 'Job started'],
                ['timestamp' => now()->subMinutes(4)->toIso8601String(), 'level' => 'info', 'message' => 'Processing chunk 1'],
                ['timestamp' => now()->subMinutes(3)->toIso8601String(), 'level' => 'info', 'message' => 'Processed 100 records'],
                ['timestamp' => now()->subMinutes(2)->toIso8601String(), 'level' => 'error', 'message' => 'Error processing record 42: Invalid data'],
                ['timestamp' => now()->subMinutes(1)->toIso8601String(), 'level' => 'info', 'message' => 'Job completed with 1 error'],
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $logs,
                'message' => 'Job logs retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving batch job logs: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve job logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}