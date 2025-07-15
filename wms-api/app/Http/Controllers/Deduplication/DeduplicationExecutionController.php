<?php

namespace App\Http\Controllers\Deduplication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deduplication\DeduplicationRule;
use App\Models\Deduplication\DeduplicationExecution;
use App\Models\Deduplication\DuplicateMatch;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DeduplicationExecutionController extends Controller
{
    /**
     * Display a listing of deduplication executions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = DeduplicationExecution::with(['rule', 'initiator']);
            
            // Apply filters
            if ($request->has('rule_id')) {
                $query->where('rule_id', $request->rule_id);
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
                'message' => 'Deduplication executions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving deduplication executions: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve deduplication executions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified deduplication execution.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $execution = DeduplicationExecution::with(['rule', 'initiator'])
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $execution,
                'message' => 'Deduplication execution retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving deduplication execution: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Deduplication execution not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Cancel a running deduplication execution.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        try {
            $execution = DeduplicationExecution::findOrFail($id);
            
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
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $execution->load(['rule', 'initiator']),
                'message' => 'Deduplication execution cancelled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling deduplication execution: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel deduplication execution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get duplicate matches for a deduplication execution.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getMatches(Request $request, $id)
    {
        try {
            $execution = DeduplicationExecution::findOrFail($id);
            
            $query = DuplicateMatch::where('execution_id', $id);
            
            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('resolution_type')) {
                $query->where('resolution_type', $request->resolution_type);
            }
            
            if ($request->has('match_group_id')) {
                $query->where('match_group_id', $request->match_group_id);
            }
            
            if ($request->has('min_score')) {
                $query->where('match_score', '>=', $request->min_score);
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'match_score');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $matches = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $matches,
                'message' => 'Duplicate matches retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving duplicate matches: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve duplicate matches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get execution statistics.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getStatistics($id)
    {
        try {
            $execution = DeduplicationExecution::findOrFail($id);
            
            // Get match statistics
            $totalMatches = DuplicateMatch::where('execution_id', $id)->count();
            $pendingMatches = DuplicateMatch::where('execution_id', $id)
                ->where('status', 'pending')
                ->count();
            $resolvedMatches = DuplicateMatch::where('execution_id', $id)
                ->where('status', 'resolved')
                ->count();
            $ignoredMatches = DuplicateMatch::where('execution_id', $id)
                ->where('status', 'ignored')
                ->count();
            
            // Get resolution statistics
            $mergedMatches = DuplicateMatch::where('execution_id', $id)
                ->where('resolution_type', 'merged')
                ->count();
            $keptMatches = DuplicateMatch::where('execution_id', $id)
                ->where('resolution_type', 'kept')
                ->count();
            $discardedMatches = DuplicateMatch::where('execution_id', $id)
                ->where('resolution_type', 'discarded')
                ->count();
            
            // Get score distribution
            $scoreRanges = [
                '0.9-1.0' => DuplicateMatch::where('execution_id', $id)
                    ->whereBetween('match_score', [0.9, 1.0])
                    ->count(),
                '0.8-0.9' => DuplicateMatch::where('execution_id', $id)
                    ->whereBetween('match_score', [0.8, 0.9])
                    ->count(),
                '0.7-0.8' => DuplicateMatch::where('execution_id', $id)
                    ->whereBetween('match_score', [0.7, 0.8])
                    ->count(),
                '0.6-0.7' => DuplicateMatch::where('execution_id', $id)
                    ->whereBetween('match_score', [0.6, 0.7])
                    ->count(),
                '0.5-0.6' => DuplicateMatch::where('execution_id', $id)
                    ->whereBetween('match_score', [0.5, 0.6])
                    ->count(),
                '0.0-0.5' => DuplicateMatch::where('execution_id', $id)
                    ->whereBetween('match_score', [0.0, 0.5])
                    ->count(),
            ];
            
            // Get match groups
            $matchGroups = DuplicateMatch::where('execution_id', $id)
                ->select('match_group_id')
                ->distinct()
                ->get()
                ->pluck('match_group_id');
                
            $groupCounts = [];
            foreach ($matchGroups as $groupId) {
                $groupCounts[$groupId] = DuplicateMatch::where('execution_id', $id)
                    ->where('match_group_id', $groupId)
                    ->count();
            }
            
            $statistics = [
                'execution' => [
                    'id' => $execution->id,
                    'rule_id' => $execution->rule_id,
                    'status' => $execution->status,
                    'total_records' => $execution->total_records,
                    'processed_records' => $execution->processed_records,
                    'duplicate_records' => $execution->duplicate_records,
                    'merged_records' => $execution->merged_records,
                    'duplicate_rate' => $execution->getDuplicateRate(),
                    'merge_rate' => $execution->getMergeRate(),
                    'duration' => $execution->getFormattedDuration(),
                ],
                'matches' => [
                    'total' => $totalMatches,
                    'pending' => $pendingMatches,
                    'resolved' => $resolvedMatches,
                    'ignored' => $ignoredMatches,
                    'resolution_rate' => $totalMatches > 0 ? round((($resolvedMatches + $ignoredMatches) / $totalMatches) * 100, 2) : 0,
                ],
                'resolutions' => [
                    'merged' => $mergedMatches,
                    'kept' => $keptMatches,
                    'discarded' => $discardedMatches,
                ],
                'score_distribution' => $scoreRanges,
                'match_groups' => [
                    'count' => count($matchGroups),
                    'groups' => $groupCounts,
                ],
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $statistics,
                'message' => 'Execution statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving execution statistics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve execution statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get execution logs.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getLogs($id)
    {
        try {
            $execution = DeduplicationExecution::findOrFail($id);
            
            // This is a placeholder - actual implementation would retrieve logs from storage
            $logs = [
                ['timestamp' => now()->subMinutes(5)->toIso8601String(), 'level' => 'info', 'message' => 'Execution started'],
                ['timestamp' => now()->subMinutes(4)->toIso8601String(), 'level' => 'info', 'message' => 'Loading records for entity type: ' . $execution->rule->entity_type],
                ['timestamp' => now()->subMinutes(3)->toIso8601String(), 'level' => 'info', 'message' => 'Comparing records using ' . ($execution->rule->isFuzzyMatch() ? 'fuzzy' : 'exact') . ' matching'],
                ['timestamp' => now()->subMinutes(2)->toIso8601String(), 'level' => 'info', 'message' => 'Found ' . $execution->duplicate_records . ' potential duplicates'],
                ['timestamp' => now()->subMinutes(1)->toIso8601String(), 'level' => 'info', 'message' => 'Auto-merged ' . $execution->merged_records . ' records'],
                ['timestamp' => now()->toIso8601String(), 'level' => 'info', 'message' => 'Execution completed'],
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
}