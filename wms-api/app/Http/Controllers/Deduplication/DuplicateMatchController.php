<?php

namespace App\Http\Controllers\Deduplication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deduplication\DuplicateMatch;
use App\Models\Deduplication\MergeResult;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateMatchController extends Controller
{
    /**
     * Display the specified duplicate match.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $match = DuplicateMatch::with(['execution.rule', 'mergeResult'])
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $match,
                'message' => 'Duplicate match retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving duplicate match: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Duplicate match not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Resolve a duplicate match by merging records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function merge(Request $request, $id)
    {
        try {
            $match = DuplicateMatch::findOrFail($id);
            
            // Check if match is already resolved
            if (!$match->isPending()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot resolve match that is not pending'
                ], 422);
            }
            
            $validator = Validator::make($request->all(), [
                'merge_strategy' => 'required|string|in:create_new,keep_source,keep_target,custom',
                'field_selections' => 'required_if:merge_strategy,custom|nullable|json',
                'is_reversible' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Validate field selections if custom merge strategy
            if ($request->merge_strategy === 'custom') {
                $fieldSelections = json_decode($request->field_selections, true);
                if (!is_array($fieldSelections) || count($fieldSelections) === 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Field selections must be a non-empty array',
                        'errors' => ['field_selections' => ['Field selections must be a non-empty array']]
                    ], 422);
                }
            }
            
            // Process merge in a transaction
            DB::beginTransaction();
            
            try {
                // Update match status
                $match->update([
                    'status' => 'resolved',
                    'resolution_type' => 'merged',
                    'resolved_by' => Auth::id(),
                    'resolved_at' => now(),
                    'resolution_notes' => $request->resolution_notes ?? 'Records merged',
                ]);
                
                // Create merge result
                $mergeResult = new MergeResult([
                    'match_id' => $match->id,
                    'entity_type' => $match->entity_type,
                    'source_record_id' => $match->record_id_1,
                    'target_record_id' => $match->record_id_2,
                    'merged_record_id' => 'merged_' . uniqid(), // In a real implementation, this would be the actual merged record ID
                    'merge_strategy' => $request->merge_strategy,
                    'field_selections' => $request->field_selections ?? [],
                    'is_reversible' => $request->is_reversible ?? true,
                    'backup_data' => [
                        'source' => ['id' => $match->record_id_1],
                        'target' => ['id' => $match->record_id_2],
                    ],
                ]);
                
                $mergeResult->save();
                
                // Update execution statistics
                $match->execution->increment('merged_records');
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'data' => $match->load(['execution.rule', 'mergeResult']),
                    'message' => 'Records merged successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error merging records: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to merge records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve a duplicate match by keeping one record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function keep(Request $request, $id)
    {
        try {
            $match = DuplicateMatch::findOrFail($id);
            
            // Check if match is already resolved
            if (!$match->isPending()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot resolve match that is not pending'
                ], 422);
            }
            
            $validator = Validator::make($request->all(), [
                'keep_record' => 'required|string|in:source,target',
                'resolution_notes' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update match status
            $match->update([
                'status' => 'resolved',
                'resolution_type' => 'kept',
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
                'resolution_notes' => $request->resolution_notes ?? 'Kept ' . $request->keep_record . ' record',
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $match->load('execution.rule'),
                'message' => 'Match resolved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error resolving match: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to resolve match',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ignore a duplicate match.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ignore(Request $request, $id)
    {
        try {
            $match = DuplicateMatch::findOrFail($id);
            
            // Check if match is already resolved
            if (!$match->isPending()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot ignore match that is not pending'
                ], 422);
            }
            
            $validator = Validator::make($request->all(), [
                'resolution_notes' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update match status
            $match->update([
                'status' => 'ignored',
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
                'resolution_notes' => $request->resolution_notes ?? 'Match ignored',
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $match->load('execution.rule'),
                'message' => 'Match ignored successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error ignoring match: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to ignore match',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Undo a match resolution.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function undoResolution($id)
    {
        try {
            $match = DuplicateMatch::findOrFail($id);
            
            // Check if match is resolved
            if ($match->isPending()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot undo resolution for pending match'
                ], 422);
            }
            
            // Process undo in a transaction
            DB::beginTransaction();
            
            try {
                // If match was merged, decrement merged_records count
                if ($match->isMerged()) {
                    $match->execution->decrement('merged_records');
                    
                    // Delete merge result
                    if ($match->mergeResult) {
                        $match->mergeResult->delete();
                    }
                }
                
                // Reset match status
                $match->update([
                    'status' => 'pending',
                    'resolution_type' => null,
                    'resolved_by' => null,
                    'resolved_at' => null,
                    'resolution_notes' => null,
                ]);
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'data' => $match->load('execution.rule'),
                    'message' => 'Match resolution undone successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error undoing match resolution: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to undo match resolution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get matches by match group.
     *
     * @param  string  $groupId
     * @return \Illuminate\Http\Response
     */
    public function getByGroup(Request $request, $groupId)
    {
        try {
            $query = DuplicateMatch::where('match_group_id', $groupId);
            
            // Apply filters
            if ($request->has('execution_id')) {
                $query->where('execution_id', $request->execution_id);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'match_score');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            $matches = $query->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $matches,
                'message' => 'Matches retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving matches by group: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve matches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve all matches in a group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $groupId
     * @return \Illuminate\Http\Response
     */
    public function resolveGroup(Request $request, $groupId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'execution_id' => 'required|exists:deduplication_executions,id',
                'resolution_type' => 'required|string|in:merge,keep,ignore',
                'merge_strategy' => 'required_if:resolution_type,merge|nullable|string|in:create_new,keep_source,keep_target,custom',
                'field_selections' => 'required_if:merge_strategy,custom|nullable|json',
                'keep_record' => 'required_if:resolution_type,keep|nullable|string|in:source,target',
                'resolution_notes' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Get pending matches in the group
            $matches = DuplicateMatch::where('match_group_id', $groupId)
                ->where('execution_id', $request->execution_id)
                ->where('status', 'pending')
                ->get();
                
            if ($matches->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No pending matches found in this group'
                ], 404);
            }
            
            // Process resolution in a transaction
            DB::beginTransaction();
            
            try {
                $resolvedCount = 0;
                
                foreach ($matches as $match) {
                    // Update match status
                    $match->status = 'resolved';
                    $match->resolved_by = Auth::id();
                    $match->resolved_at = now();
                    $match->resolution_notes = $request->resolution_notes ?? 'Group resolution';
                    
                    switch ($request->resolution_type) {
                        case 'merge':
                            $match->resolution_type = 'merged';
                            
                            // Create merge result
                            $mergeResult = new MergeResult([
                                'match_id' => $match->id,
                                'entity_type' => $match->entity_type,
                                'source_record_id' => $match->record_id_1,
                                'target_record_id' => $match->record_id_2,
                                'merged_record_id' => 'merged_' . uniqid(), // In a real implementation, this would be the actual merged record ID
                                'merge_strategy' => $request->merge_strategy,
                                'field_selections' => $request->field_selections ?? [],
                                'is_reversible' => $request->is_reversible ?? true,
                                'backup_data' => [
                                    'source' => ['id' => $match->record_id_1],
                                    'target' => ['id' => $match->record_id_2],
                                ],
                            ]);
                            
                            $mergeResult->save();
                            
                            // Update execution statistics
                            $match->execution->increment('merged_records');
                            break;
                            
                        case 'keep':
                            $match->resolution_type = 'kept';
                            break;
                            
                        case 'ignore':
                            $match->status = 'ignored';
                            break;
                    }
                    
                    $match->save();
                    $resolvedCount++;
                }
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'resolved_count' => $resolvedCount,
                        'group_id' => $groupId
                    ],
                    'message' => 'Group resolved successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error resolving match group: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to resolve match group',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}