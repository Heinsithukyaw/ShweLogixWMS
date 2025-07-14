<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflow\WorkflowDefinition;
use App\Models\Workflow\WorkflowInstance;
use App\Models\Workflow\WorkflowStep;
use App\Models\Workflow\WorkflowStepInstance;
use App\Models\Workflow\WorkflowTransition;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowInstanceController extends Controller
{
    /**
     * Display a listing of workflow instances.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = WorkflowInstance::with(['workflowDefinition', 'initiator']);
            
            // Apply filters
            if ($request->has('workflow_definition_id')) {
                $query->where('workflow_definition_id', $request->workflow_definition_id);
            }
            
            if ($request->has('entity_type')) {
                $query->where('entity_type', $request->entity_type);
            }
            
            if ($request->has('entity_id')) {
                $query->where('entity_id', $request->entity_id);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('initiated_by')) {
                $query->where('initiated_by', $request->initiated_by);
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
                'message' => 'Workflow instances retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow instances: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve workflow instances',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a new workflow instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function start(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'workflow_definition_id' => 'required|exists:workflow_definitions,id',
                'entity_type' => 'required|string|max:100',
                'entity_id' => 'required|string|max:100',
                'workflow_data' => 'nullable|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Get workflow definition
            $workflowDefinition = WorkflowDefinition::with('steps')->findOrFail($request->workflow_definition_id);
            
            // Check if workflow is active
            if (!$workflowDefinition->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot start instance of inactive workflow'
                ], 422);
            }
            
            // Get start step
            $startStep = $workflowDefinition->startStep();
            
            if (!$startStep) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Workflow definition has no start step'
                ], 422);
            }
            
            // Check if there's already an active workflow for this entity
            $existingInstance = WorkflowInstance::where('entity_type', $request->entity_type)
                ->where('entity_id', $request->entity_id)
                ->where('status', 'active')
                ->first();
                
            if ($existingInstance) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'An active workflow already exists for this entity',
                    'data' => $existingInstance
                ], 422);
            }
            
            // Start workflow instance in a transaction
            DB::beginTransaction();
            
            try {
                // Create workflow instance
                $instance = new WorkflowInstance([
                    'workflow_definition_id' => $workflowDefinition->id,
                    'entity_type' => $request->entity_type,
                    'entity_id' => $request->entity_id,
                    'current_step_code' => $startStep->step_code,
                    'status' => 'active',
                    'initiated_by' => Auth::id(),
                    'workflow_data' => $request->workflow_data ?? [],
                ]);
                
                $instance->save();
                
                // Create step instance for start step
                $stepInstance = new WorkflowStepInstance([
                    'workflow_instance_id' => $instance->id,
                    'workflow_step_id' => $startStep->id,
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'step_data' => [],
                ]);
                
                $stepInstance->save();
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'data' => $instance->load(['workflowDefinition', 'initiator', 'currentStepInstance']),
                    'message' => 'Workflow instance started successfully'
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error starting workflow instance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start workflow instance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified workflow instance.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $instance = WorkflowInstance::with([
                'workflowDefinition',
                'initiator',
                'stepInstances.workflowStep',
                'stepInstances.assignee',
                'stepInstances.completedBy',
                'transitions.triggeredBy'
            ])->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $instance,
                'message' => 'Workflow instance retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow instance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Workflow instance not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Transition a workflow instance to the next step.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function transition(Request $request, $id)
    {
        try {
            $instance = WorkflowInstance::findOrFail($id);
            
            // Check if workflow is active
            if (!$instance->isActive()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot transition inactive workflow instance'
                ], 422);
            }
            
            $validator = Validator::make($request->all(), [
                'to_step_code' => 'required|string|max:50',
                'transition_reason' => 'nullable|string',
                'transition_data' => 'nullable|json',
                'step_data' => 'nullable|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Get current step
            $currentStep = $instance->currentStep();
            
            if (!$currentStep) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Current step not found'
                ], 422);
            }
            
            // Get target step
            $toStep = WorkflowStep::where('workflow_definition_id', $instance->workflow_definition_id)
                ->where('step_code', $request->to_step_code)
                ->first();
                
            if (!$toStep) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Target step not found'
                ], 422);
            }
            
            // Check if transition is valid
            $nextSteps = $currentStep->getNextSteps($instance->workflow_data);
            
            if (!in_array($toStep->step_code, $nextSteps) && $request->transition_type !== 'skip' && $request->transition_type !== 'rollback') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid transition. Target step is not in the list of valid next steps',
                    'valid_next_steps' => $nextSteps
                ], 422);
            }
            
            // Perform transition in a transaction
            DB::beginTransaction();
            
            try {
                // Complete current step instance
                $currentStepInstance = $instance->currentStepInstance();
                
                if ($currentStepInstance) {
                    $currentStepInstance->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'completed_by' => Auth::id(),
                        'step_data' => array_merge($currentStepInstance->step_data ?? [], $request->step_data ?? []),
                        'notes' => $request->transition_reason,
                    ]);
                }
                
                // Create transition record
                $transition = new WorkflowTransition([
                    'workflow_instance_id' => $instance->id,
                    'from_step_code' => $instance->current_step_code,
                    'to_step_code' => $toStep->step_code,
                    'transition_type' => $request->transition_type ?? 'normal',
                    'triggered_by' => Auth::id(),
                    'transition_reason' => $request->transition_reason,
                    'transition_data' => $request->transition_data,
                ]);
                
                $transition->save();
                
                // Create new step instance
                $stepInstance = new WorkflowStepInstance([
                    'workflow_instance_id' => $instance->id,
                    'workflow_step_id' => $toStep->id,
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'step_data' => [],
                ]);
                
                $stepInstance->save();
                
                // Update workflow instance
                $instance->update([
                    'current_step_code' => $toStep->step_code,
                    'workflow_data' => array_merge($instance->workflow_data ?? [], $request->step_data ?? []),
                ]);
                
                // If this is an end step, complete the workflow
                if ($toStep->is_end_step) {
                    $instance->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                    
                    $stepInstance->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'completed_by' => Auth::id(),
                    ]);
                }
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'data' => $instance->load(['workflowDefinition', 'currentStepInstance']),
                    'message' => 'Workflow transitioned successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error transitioning workflow instance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to transition workflow instance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a workflow instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel(Request $request, $id)
    {
        try {
            $instance = WorkflowInstance::findOrFail($id);
            
            // Check if workflow is active
            if (!$instance->isActive()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel inactive workflow instance'
                ], 422);
            }
            
            $validator = Validator::make($request->all(), [
                'cancellation_reason' => 'required|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Cancel workflow in a transaction
            DB::beginTransaction();
            
            try {
                // Complete current step instance
                $currentStepInstance = $instance->currentStepInstance();
                
                if ($currentStepInstance) {
                    $currentStepInstance->update([
                        'status' => 'cancelled',
                        'completed_at' => now(),
                        'completed_by' => Auth::id(),
                        'notes' => $request->cancellation_reason,
                    ]);
                }
                
                // Create transition record
                $transition = new WorkflowTransition([
                    'workflow_instance_id' => $instance->id,
                    'from_step_code' => $instance->current_step_code,
                    'to_step_code' => null,
                    'transition_type' => 'cancel',
                    'triggered_by' => Auth::id(),
                    'transition_reason' => $request->cancellation_reason,
                ]);
                
                $transition->save();
                
                // Update workflow instance
                $instance->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $request->cancellation_reason,
                    'completed_at' => now(),
                ]);
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'data' => $instance,
                    'message' => 'Workflow cancelled successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error cancelling workflow instance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel workflow instance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update workflow instance data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateData(Request $request, $id)
    {
        try {
            $instance = WorkflowInstance::findOrFail($id);
            
            // Check if workflow is active
            if (!$instance->isActive()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update data for inactive workflow instance'
                ], 422);
            }
            
            $validator = Validator::make($request->all(), [
                'workflow_data' => 'required|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update workflow data
            $instance->update([
                'workflow_data' => array_merge($instance->workflow_data ?? [], $request->workflow_data),
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $instance,
                'message' => 'Workflow data updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating workflow instance data: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update workflow data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get workflow instances for a specific entity.
     *
     * @param  string  $entityType
     * @param  string  $entityId
     * @return \Illuminate\Http\Response
     */
    public function getByEntity($entityType, $entityId)
    {
        try {
            $instances = WorkflowInstance::with(['workflowDefinition', 'initiator'])
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $instances,
                'message' => 'Workflow instances retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow instances by entity: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve workflow instances',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active workflow instance for a specific entity.
     *
     * @param  string  $entityType
     * @param  string  $entityId
     * @return \Illuminate\Http\Response
     */
    public function getActiveByEntity($entityType, $entityId)
    {
        try {
            $instance = WorkflowInstance::with(['workflowDefinition', 'initiator', 'currentStepInstance'])
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->where('status', 'active')
                ->first();
            
            if (!$instance) {
                return response()->json([
                    'status' => 'success',
                    'data' => null,
                    'message' => 'No active workflow instance found for this entity'
                ]);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $instance,
                'message' => 'Active workflow instance retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving active workflow instance by entity: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve active workflow instance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}