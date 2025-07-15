<?php

namespace App\Http\Controllers\DataLineage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataLineage\DataFlow;
use App\Models\DataLineage\DataFlowExecution;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DataFlowExecutionController extends Controller
{
    /**
     * Display a listing of data flow executions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = DataFlowExecution::with(['flow', 'initiator']);
            
            // Apply filters
            if ($request->has('flow_id')) {
                $query->where('flow_id', $request->flow_id);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('initiated_by')) {
                $query->where('initiated_by', $request->initiated_by);
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
            $executions = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $executions,
                'message' => 'Data flow executions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving data flow executions: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve data flow executions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified data flow execution.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $execution = DataFlowExecution::with(['flow', 'initiator'])
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $execution,
                'message' => 'Data flow execution retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving data flow execution: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Data flow execution not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Cancel a running data flow execution.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        try {
            $execution = DataFlowExecution::findOrFail($id);
            
            // Check if execution can be cancelled
            if (!in_array($execution->status, ['queued', 'running'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel execution that is not queued or running'
                ], 422);
            }
            
            // Update execution status
            $execution->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'error_message' => 'Execution cancelled by user'
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $execution->load(['flow', 'initiator']),
                'message' => 'Data flow execution cancelled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling data flow execution: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel data flow execution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry a failed data flow execution.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function retry($id)
    {
        try {
            $execution = DataFlowExecution::findOrFail($id);
            
            // Check if execution can be retried
            if (!in_array($execution->status, ['failed', 'cancelled'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot retry execution that is not failed or cancelled'
                ], 422);
            }
            
            // Check if flow is active
            $flow = DataFlow::findOrFail($execution->flow_id);
            if (!$flow->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot retry execution for inactive flow'
                ], 422);
            }
            
            // Create new execution record
            $newExecution = new DataFlowExecution([
                'flow_id' => $execution->flow_id,
                'execution_id' => uniqid('exec_'),
                'status' => 'queued',
                'execution_parameters' => $execution->execution_parameters,
                'initiated_by' => auth()->id(),
            ]);
            
            $newExecution->save();
            
            // Process flow execution
            // This is a placeholder - actual implementation would use a job queue
            $result = $this->processFlowExecution($newExecution);
            
            return response()->json([
                'status' => 'success',
                'data' => $newExecution->fresh()->load(['flow', 'initiator']),
                'message' => 'Data flow execution retried successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrying data flow execution: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retry data flow execution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get execution logs for a data flow execution.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getLogs($id)
    {
        try {
            $execution = DataFlowExecution::findOrFail($id);
            
            // This is a placeholder - actual implementation would retrieve logs from storage
            $logs = [
                ['timestamp' => now()->subMinutes(5)->toIso8601String(), 'level' => 'info', 'message' => 'Execution started'],
                ['timestamp' => now()->subMinutes(4)->toIso8601String(), 'level' => 'info', 'message' => 'Extracting data from source'],
                ['timestamp' => now()->subMinutes(3)->toIso8601String(), 'level' => 'info', 'message' => 'Applying transformations'],
                ['timestamp' => now()->subMinutes(2)->toIso8601String(), 'level' => 'error', 'message' => 'Error processing record 42: Invalid data'],
                ['timestamp' => now()->subMinutes(1)->toIso8601String(), 'level' => 'info', 'message' => 'Execution completed with 1 error'],
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $logs,
                'message' => 'Execution logs retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving execution logs: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve execution logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get execution statistics for a data flow.
     *
     * @param  int  $flowId
     * @return \Illuminate\Http\Response
     */
    public function getFlowStatistics($flowId)
    {
        try {
            $flow = DataFlow::findOrFail($flowId);
            
            // Get execution statistics
            $totalExecutions = DataFlowExecution::where('flow_id', $flowId)->count();
            $successfulExecutions = DataFlowExecution::where('flow_id', $flowId)
                ->where('status', 'completed')
                ->count();
            $failedExecutions = DataFlowExecution::where('flow_id', $flowId)
                ->whereIn('status', ['failed', 'cancelled'])
                ->count();
            
            // Get average execution time
            $completedExecutions = DataFlowExecution::where('flow_id', $flowId)
                ->where('status', 'completed')
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->get();
                
            $avgExecutionTime = 0;
            if ($completedExecutions->count() > 0) {
                $totalTime = 0;
                foreach ($completedExecutions as $execution) {
                    $totalTime += $execution->getDurationInSeconds();
                }
                $avgExecutionTime = $totalTime / $completedExecutions->count();
            }
            
            // Get total records processed
            $totalRecordsProcessed = DataFlowExecution::where('flow_id', $flowId)
                ->sum('processed_records');
            $totalSuccessRecords = DataFlowExecution::where('flow_id', $flowId)
                ->sum('success_records');
            $totalErrorRecords = DataFlowExecution::where('flow_id', $flowId)
                ->sum('error_records');
            
            // Get recent executions
            $recentExecutions = DataFlowExecution::where('flow_id', $flowId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            $statistics = [
                'total_executions' => $totalExecutions,
                'successful_executions' => $successfulExecutions,
                'failed_executions' => $failedExecutions,
                'success_rate' => $totalExecutions > 0 ? round(($successfulExecutions / $totalExecutions) * 100, 2) : 0,
                'avg_execution_time_seconds' => round($avgExecutionTime, 2),
                'avg_execution_time_formatted' => $this->formatDuration($avgExecutionTime),
                'total_records_processed' => $totalRecordsProcessed,
                'total_success_records' => $totalSuccessRecords,
                'total_error_records' => $totalErrorRecords,
                'error_rate' => $totalRecordsProcessed > 0 ? round(($totalErrorRecords / $totalRecordsProcessed) * 100, 2) : 0,
                'recent_executions' => $recentExecutions,
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $statistics,
                'message' => 'Flow statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving flow statistics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve flow statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a data flow execution.
     *
     * @param  \App\Models\DataLineage\DataFlowExecution  $execution
     * @return bool
     */
    private function processFlowExecution($execution)
    {
        // This is a placeholder - actual implementation would handle the data flow execution
        // based on the flow configuration, field mappings, and transformations
        
        // Update execution status to simulate processing
        $execution->update([
            'status' => 'running',
            'started_at' => now(),
            'total_records' => 100,
        ]);
        
        // Simulate successful execution
        $execution->update([
            'status' => 'completed',
            'completed_at' => now(),
            'processed_records' => 100,
            'success_records' => 95,
            'error_records' => 5,
        ]);
        
        return true;
    }

    /**
     * Format duration in seconds to human-readable format.
     *
     * @param  int  $seconds
     * @return string
     */
    private function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}