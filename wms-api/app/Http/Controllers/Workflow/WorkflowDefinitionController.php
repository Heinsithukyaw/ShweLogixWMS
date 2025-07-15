<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflow\WorkflowDefinition;
use App\Models\Workflow\WorkflowStep;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowDefinitionController extends Controller
{
    /**
     * Display a listing of workflow definitions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = WorkflowDefinition::query();
            
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
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $workflows = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $workflows,
                'message' => 'Workflow definitions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow definitions: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve workflow definitions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created workflow definition.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:workflow_definitions',
                'description' => 'nullable|string',
                'entity_type' => 'required|string|max:100',
                'workflow_schema' => 'nullable|json',
                'is_active' => 'boolean',
                'version' => 'integer',
                'steps' => 'required|array|min:1',
                'steps.*.step_code' => 'required|string|max:50',
                'steps.*.name' => 'required|string|max:255',
                'steps.*.description' => 'nullable|string',
                'steps.*.step_type' => 'required|string|max:50',
                'steps.*.step_configuration' => 'nullable|json',
                'steps.*.transition_rules' => 'nullable|json',
                'steps.*.is_start_step' => 'boolean',
                'steps.*.is_end_step' => 'boolean',
                'steps.*.timeout_minutes' => 'nullable|integer',
                'steps.*.timeout_action' => 'nullable|string|max:50',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Validate workflow structure
            $steps = $request->steps;
            
            // Check for start step
            $startSteps = array_filter($steps, function($step) {
                return isset($step['is_start_step']) && $step['is_start_step'];
            });
            
            if (count($startSteps) !== 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Workflow must have exactly one start step'
                ], 422);
            }
            
            // Check for at least one end step
            $endSteps = array_filter($steps, function($step) {
                return isset($step['is_end_step']) && $step['is_end_step'];
            });
            
            if (count($endSteps) < 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Workflow must have at least one end step'
                ], 422);
            }
            
            // Check for unique step codes
            $stepCodes = array_column($steps, 'step_code');
            if (count($stepCodes) !== count(array_unique($stepCodes))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Step codes must be unique within the workflow'
                ], 422);
            }
            
            // Create workflow definition and steps in a transaction
            DB::beginTransaction();
            
            try {
                // Create workflow definition
                $workflow = WorkflowDefinition::create([
                    'name' => $request->name,
                    'code' => $request->code,
                    'description' => $request->description,
                    'entity_type' => $request->entity_type,
                    'workflow_schema' => $request->workflow_schema,
                    'is_active' => $request->is_active ?? true,
                    'version' => $request->version ?? 1,
                ]);
                
                // Create workflow steps
                foreach ($steps as $stepData) {
                    $step = new WorkflowStep([
                        'workflow_definition_id' => $workflow->id,
                        'step_code' => $stepData['step_code'],
                        'name' => $stepData['name'],
                        'description' => $stepData['description'] ?? null,
                        'step_type' => $stepData['step_type'],
                        'step_configuration' => $stepData['step_configuration'] ?? null,
                        'transition_rules' => $stepData['transition_rules'] ?? null,
                        'is_start_step' => $stepData['is_start_step'] ?? false,
                        'is_end_step' => $stepData['is_end_step'] ?? false,
                        'timeout_minutes' => $stepData['timeout_minutes'] ?? null,
                        'timeout_action' => $stepData['timeout_action'] ?? null,
                    ]);
                    
                    $step->save();
                }
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'data' => $workflow->load('steps'),
                    'message' => 'Workflow definition created successfully'
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error creating workflow definition: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create workflow definition',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified workflow definition.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $workflow = WorkflowDefinition::with('steps')->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $workflow,
                'message' => 'Workflow definition retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow definition: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Workflow definition not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified workflow definition.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $workflow = WorkflowDefinition::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'code' => 'string|max:50|unique:workflow_definitions,code,' . $id,
                'description' => 'nullable|string',
                'entity_type' => 'string|max:100',
                'workflow_schema' => 'nullable|json',
                'is_active' => 'boolean',
                'version' => 'integer',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update workflow definition
            $workflow->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $workflow->load('steps'),
                'message' => 'Workflow definition updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating workflow definition: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update workflow definition',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified workflow definition.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $workflow = WorkflowDefinition::findOrFail($id);
            
            // Check if there are active workflow instances
            if ($workflow->instances()->where('status', 'active')->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete workflow with active instances'
                ], 422);
            }
            
            // Delete workflow steps and definition
            DB::beginTransaction();
            
            try {
                $workflow->steps()->delete();
                $workflow->delete();
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Workflow definition deleted successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error deleting workflow definition: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete workflow definition',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new version of a workflow definition.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function createVersion(Request $request, $id)
    {
        try {
            $workflow = WorkflowDefinition::with('steps')->findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'description' => 'nullable|string',
                'workflow_schema' => 'nullable|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Create new version in a transaction
            DB::beginTransaction();
            
            try {
                // Create new workflow definition
                $newWorkflow = new WorkflowDefinition([
                    'name' => $request->name ?? $workflow->name,
                    'code' => $workflow->code . '_v' . ($workflow->version + 1),
                    'description' => $request->description ?? $workflow->description,
                    'entity_type' => $workflow->entity_type,
                    'workflow_schema' => $request->workflow_schema ?? $workflow->workflow_schema,
                    'is_active' => $request->is_active ?? false,
                    'version' => $workflow->version + 1,
                ]);
                
                $newWorkflow->save();
                
                // Copy workflow steps
                foreach ($workflow->steps as $step) {
                    $newStep = new WorkflowStep([
                        'workflow_definition_id' => $newWorkflow->id,
                        'step_code' => $step->step_code,
                        'name' => $step->name,
                        'description' => $step->description,
                        'step_type' => $step->step_type,
                        'step_configuration' => $step->step_configuration,
                        'transition_rules' => $step->transition_rules,
                        'is_start_step' => $step->is_start_step,
                        'is_end_step' => $step->is_end_step,
                        'timeout_minutes' => $step->timeout_minutes,
                        'timeout_action' => $step->timeout_action,
                    ]);
                    
                    $newStep->save();
                }
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'data' => $newWorkflow->load('steps'),
                    'message' => 'New workflow version created successfully'
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error creating workflow version: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create workflow version',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all workflow definitions for a specific entity type.
     *
     * @param  string  $entityType
     * @return \Illuminate\Http\Response
     */
    public function getByEntityType($entityType)
    {
        try {
            $workflows = WorkflowDefinition::where('entity_type', $entityType)
                ->where('is_active', true)
                ->orderBy('version', 'desc')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $workflows,
                'message' => 'Workflow definitions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow definitions by entity type: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve workflow definitions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}