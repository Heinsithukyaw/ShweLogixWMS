<?php

namespace App\Http\Controllers\Deduplication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deduplication\DeduplicationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DeduplicationRuleController extends Controller
{
    /**
     * Display a listing of deduplication rules.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = DeduplicationRule::query();
            
            // Apply filters
            if ($request->has('entity_type')) {
                $query->where('entity_type', $request->entity_type);
            }
            
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('rule_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $rules = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $rules,
                'message' => 'Deduplication rules retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving deduplication rules: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve deduplication rules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created deduplication rule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'rule_code' => 'required|string|max:50|unique:deduplication_rules',
                'description' => 'nullable|string',
                'entity_type' => 'required|string|max:100',
                'match_fields' => 'required|json',
                'match_threshold' => 'required|numeric|min:0|max:1',
                'fuzzy_match_config' => 'nullable|json',
                'action_on_match' => 'required|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Validate match fields
            $matchFields = json_decode($request->match_fields, true);
            if (!is_array($matchFields) || count($matchFields) === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Match fields must be a non-empty array',
                    'errors' => ['match_fields' => ['Match fields must be a non-empty array']]
                ], 422);
            }
            
            // Validate action on match
            $actionOnMatch = json_decode($request->action_on_match, true);
            if (!isset($actionOnMatch['action_type']) || !in_array($actionOnMatch['action_type'], ['flag', 'prevent', 'auto_merge'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Action on match must contain a valid action type',
                    'errors' => ['action_on_match' => ['Action type must be one of: flag, prevent, auto_merge']]
                ], 422);
            }
            
            $rule = DeduplicationRule::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $rule,
                'message' => 'Deduplication rule created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating deduplication rule: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create deduplication rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified deduplication rule.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $rule = DeduplicationRule::with('executions')
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $rule,
                'message' => 'Deduplication rule retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving deduplication rule: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Deduplication rule not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified deduplication rule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $rule = DeduplicationRule::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'rule_code' => 'string|max:50|unique:deduplication_rules,rule_code,' . $id,
                'description' => 'nullable|string',
                'entity_type' => 'string|max:100',
                'match_fields' => 'json',
                'match_threshold' => 'numeric|min:0|max:1',
                'fuzzy_match_config' => 'nullable|json',
                'action_on_match' => 'json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Validate match fields if provided
            if ($request->has('match_fields')) {
                $matchFields = json_decode($request->match_fields, true);
                if (!is_array($matchFields) || count($matchFields) === 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Match fields must be a non-empty array',
                        'errors' => ['match_fields' => ['Match fields must be a non-empty array']]
                    ], 422);
                }
            }
            
            // Validate action on match if provided
            if ($request->has('action_on_match')) {
                $actionOnMatch = json_decode($request->action_on_match, true);
                if (!isset($actionOnMatch['action_type']) || !in_array($actionOnMatch['action_type'], ['flag', 'prevent', 'auto_merge'])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Action on match must contain a valid action type',
                        'errors' => ['action_on_match' => ['Action type must be one of: flag, prevent, auto_merge']]
                    ], 422);
                }
            }
            
            $rule->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $rule,
                'message' => 'Deduplication rule updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating deduplication rule: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update deduplication rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified deduplication rule.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $rule = DeduplicationRule::findOrFail($id);
            
            // Check if there are related executions
            if ($rule->executions()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete rule with associated executions'
                ], 422);
            }
            
            $rule->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Deduplication rule deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting deduplication rule: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete deduplication rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute a deduplication rule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function executeRule(Request $request, $id)
    {
        try {
            $rule = DeduplicationRule::findOrFail($id);
            
            // Check if rule is active
            if (!$rule->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot execute inactive rule'
                ], 422);
            }
            
            $validator = Validator::make($request->all(), [
                'execution_parameters' => 'nullable|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Create execution record
            $execution = new \App\Models\Deduplication\DeduplicationExecution([
                'rule_id' => $id,
                'execution_id' => uniqid('dedup_'),
                'status' => 'queued',
                'execution_parameters' => $request->execution_parameters ?? [],
                'initiated_by' => auth()->id(),
            ]);
            
            $execution->save();
            
            // Process rule execution
            // This is a placeholder - actual implementation would use a job queue
            $result = $this->processRuleExecution($execution);
            
            return response()->json([
                'status' => 'success',
                'data' => $execution->fresh(),
                'message' => 'Deduplication rule execution initiated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error executing deduplication rule: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to execute deduplication rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a deduplication rule execution.
     *
     * @param  \App\Models\Deduplication\DeduplicationExecution  $execution
     * @return bool
     */
    private function processRuleExecution($execution)
    {
        // This is a placeholder - actual implementation would handle the deduplication rule execution
        // based on the rule configuration
        
        // Update execution status to simulate processing
        $execution->update([
            'status' => 'running',
            'started_at' => now(),
            'total_records' => 1000,
        ]);
        
        // Simulate successful execution
        $execution->update([
            'status' => 'completed',
            'completed_at' => now(),
            'processed_records' => 1000,
            'duplicate_records' => 50,
            'merged_records' => 30,
        ]);
        
        // Create some sample duplicate matches
        for ($i = 1; $i <= 50; $i++) {
            $match = new \App\Models\Deduplication\DuplicateMatch([
                'execution_id' => $execution->id,
                'match_group_id' => 'group_' . ceil($i / 2),
                'entity_type' => $execution->rule->entity_type,
                'record_id_1' => 'record_' . $i,
                'record_id_2' => 'record_' . ($i + 50),
                'match_score' => rand(80, 100) / 100,
                'match_details' => [
                    'matched_fields' => ['name', 'email', 'phone'],
                    'field_scores' => [
                        'name' => rand(70, 100) / 100,
                        'email' => rand(80, 100) / 100,
                        'phone' => rand(60, 100) / 100,
                    ]
                ],
                'status' => $i <= 30 ? 'resolved' : 'pending',
                'resolution_type' => $i <= 30 ? 'merged' : null,
            ]);
            
            $match->save();
            
            // Create merge result for resolved matches
            if ($i <= 30) {
                $mergeResult = new \App\Models\Deduplication\MergeResult([
                    'match_id' => $match->id,
                    'entity_type' => $match->entity_type,
                    'source_record_id' => $match->record_id_1,
                    'target_record_id' => $match->record_id_2,
                    'merged_record_id' => 'merged_' . $i,
                    'merge_strategy' => 'custom',
                    'field_selections' => [
                        'name' => 'source',
                        'email' => 'target',
                        'phone' => 'source',
                        'address' => 'target',
                    ],
                    'is_reversible' => true,
                    'backup_data' => [
                        'source' => ['id' => $match->record_id_1],
                        'target' => ['id' => $match->record_id_2],
                    ],
                ]);
                
                $mergeResult->save();
            }
        }
        
        return true;
    }
}