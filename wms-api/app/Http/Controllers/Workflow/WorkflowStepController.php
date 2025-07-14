<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflow\WorkflowDefinition;
use App\Models\Workflow\WorkflowStep;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class WorkflowStepController extends Controller
{
    /**
     * Display a listing of workflow steps for a workflow definition.
     *
     * @param  int  $workflowId
     * @return \Illuminate\Http\Response
     */
    public function index($workflowId)
    {
        try {
            $workflow = WorkflowDefinition::findOrFail($workflowId);
            
            $steps = WorkflowStep::where('workflow_definition_id', $workflowId)
                ->orderBy('is_start_step', 'desc')
                ->orderBy('step_code')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $steps,
                'message' => 'Workflow steps retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow steps: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve workflow steps',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created workflow step.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $workflowId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $workflowId)
    {
        try {
            $workflow = WorkflowDefinition::findOrFail($workflowId);
            
            $validator = Validator::make($request->all(), [
                'step_code' => 'required|string|max:50',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'step_type' => 'required|string|max:50',
                'step_configuration' => 'nullable|json',
                'transition_rules' => 'nullable|json',
                'is_start_step' => 'boolean',
                'is_end_step' => 'boolean',
                'timeout_minutes' => 'nullable|integer',
                'timeout_action' => 'nullable|string|max:50',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if step code is unique for this workflow
            $existingStep = WorkflowStep::where('workflow_definition_id', $workflowId)
                ->where('step_code', $request->step_code)
                ->first();
                
            if ($existingStep) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Step code must be unique within the workflow'
                ], 422);
            }
            
            // If this is a start step, ensure no other start steps exist
            if ($request->is_start_step) {
                $existingStartStep = WorkflowStep::where('workflow_definition_id', $workflowId)
                    ->where('is_start_step', true)
                    ->first();
                    
                if ($existingStartStep) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Workflow already has a start step'
                    ], 422);
                }
            }
            
            // Create workflow step
            $step = new WorkflowStep([
                'workflow_definition_id' => $workflowId,
                'step_code' => $request->step_code,
                'name' => $request->name,
                'description' => $request->description,
                'step_type' => $request->step_type,
                'step_configuration' => $request->step_configuration,
                'transition_rules' => $request->transition_rules,
                'is_start_step' => $request->is_start_step ?? false,
                'is_end_step' => $request->is_end_step ?? false,
                'timeout_minutes' => $request->timeout_minutes,
                'timeout_action' => $request->timeout_action,
            ]);
            
            $step->save();
            
            return response()->json([
                'status' => 'success',
                'data' => $step,
                'message' => 'Workflow step created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating workflow step: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create workflow step',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified workflow step.
     *
     * @param  int  $workflowId
     * @param  int  $stepId
     * @return \Illuminate\Http\Response
     */
    public function show($workflowId, $stepId)
    {
        try {
            $workflow = WorkflowDefinition::findOrFail($workflowId);
            
            $step = WorkflowStep::where('workflow_definition_id', $workflowId)
                ->where('id', $stepId)
                ->firstOrFail();
            
            return response()->json([
                'status' => 'success',
                'data' => $step,
                'message' => 'Workflow step retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow step: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Workflow step not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified workflow step.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $workflowId
     * @param  int  $stepId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $workflowId, $stepId)
    {
        try {
            $workflow = WorkflowDefinition::findOrFail($workflowId);
            
            $step = WorkflowStep::where('workflow_definition_id', $workflowId)
                ->where('id', $stepId)
                ->firstOrFail();
            
            $validator = Validator::make($request->all(), [
                'step_code' => 'string|max:50',
                'name' => 'string|max:255',
                'description' => 'nullable|string',
                'step_type' => 'string|max:50',
                'step_configuration' => 'nullable|json',
                'transition_rules' => 'nullable|json',
                'is_start_step' => 'boolean',
                'is_end_step' => 'boolean',
                'timeout_minutes' => 'nullable|integer',
                'timeout_action' => 'nullable|string|max:50',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if step code is unique for this workflow
            if ($request->has('step_code') && $request->step_code !== $step->step_code) {
                $existingStep = WorkflowStep::where('workflow_definition_id', $workflowId)
                    ->where('step_code', $request->step_code)
                    ->where('id', '!=', $stepId)
                    ->first();
                    
                if ($existingStep) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Step code must be unique within the workflow'
                    ], 422);
                }
            }
            
            // If this is a start step, ensure no other start steps exist
            if ($request->has('is_start_step') && $request->is_start_step && !$step->is_start_step) {
                $existingStartStep = WorkflowStep::where('workflow_definition_id', $workflowId)
                    ->where('is_start_step', true)
                    ->where('id', '!=', $stepId)
                    ->first();
                    
                if ($existingStartStep) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Workflow already has a start step'
                    ], 422);
                }
            }
            
            // Update workflow step
            $step->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $step,
                'message' => 'Workflow step updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating workflow step: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update workflow step',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified workflow step.
     *
     * @param  int  $workflowId
     * @param  int  $stepId
     * @return \Illuminate\Http\Response
     */
    public function destroy($workflowId, $stepId)
    {
        try {
            $workflow = WorkflowDefinition::findOrFail($workflowId);
            
            $step = WorkflowStep::where('workflow_definition_id', $workflowId)
                ->where('id', $stepId)
                ->firstOrFail();
            
            // Check if step is referenced in transition rules of other steps
            $referencedInSteps = WorkflowStep::where('workflow_definition_id', $workflowId)
                ->where('id', '!=', $stepId)
                ->get();
                
            foreach ($referencedInSteps as $otherStep) {
                if ($otherStep->transition_rules) {
                    foreach ($otherStep->transition_rules as $rule) {
                        if (isset($rule['next_step']) && $rule['next_step'] === $step->step_code) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Cannot delete step that is referenced in transition rules of other steps'
                            ], 422);
                        }
                    }
                }
            }
            
            // Delete workflow step
            $step->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Workflow step deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting workflow step: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete workflow step',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get workflow step by step code.
     *
     * @param  int  $workflowId
     * @param  string  $stepCode
     * @return \Illuminate\Http\Response
     */
    public function getByStepCode($workflowId, $stepCode)
    {
        try {
            $workflow = WorkflowDefinition::findOrFail($workflowId);
            
            $step = WorkflowStep::where('workflow_definition_id', $workflowId)
                ->where('step_code', $stepCode)
                ->firstOrFail();
            
            return response()->json([
                'status' => 'success',
                'data' => $step,
                'message' => 'Workflow step retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow step by code: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Workflow step not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}