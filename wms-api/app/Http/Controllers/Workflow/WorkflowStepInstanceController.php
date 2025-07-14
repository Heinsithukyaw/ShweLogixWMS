<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflow\WorkflowInstance;
use App\Models\Workflow\WorkflowStepInstance;
use App\Models\Workflow\WorkflowApproval;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WorkflowStepInstanceController extends Controller
{
    /**
     * Display a listing of step instances for a workflow instance.
     *
     * @param  int  $instanceId
     * @return \Illuminate\Http\Response
     */
    public function index($instanceId)
    {
        try {
            $instance = WorkflowInstance::findOrFail($instanceId);
            
            $stepInstances = WorkflowStepInstance::with(['workflowStep', 'assignee', 'completedBy'])
                ->where('workflow_instance_id', $instanceId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $stepInstances,
                'message' => 'Workflow step instances retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow step instances: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve workflow step instances',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified step instance.
     *
     * @param  int  $instanceId
     * @param  int  $stepInstanceId
     * @return \Illuminate\Http\Response
     */
    public function show($instanceId, $stepInstanceId)
    {
        try {
            $instance = WorkflowInstance::findOrFail($instanceId);
            
            $stepInstance = WorkflowStepInstance::with(['workflowStep', 'assignee', 'completedBy', 'approvals'])
                ->where('workflow_instance_id', $instanceId)
                ->where('id', $stepInstanceId)
                ->firstOrFail();
            
            return response()->json([
                'status' => 'success',
                'data' => $stepInstance,
                'message' => 'Workflow step instance retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow step instance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Workflow step instance not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified step instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $instanceId
     * @param  int  $stepInstanceId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $instanceId, $stepInstanceId)
    {
        try {
            $instance = WorkflowInstance::findOrFail($instanceId);
            
            // Check if workflow is active
            if (!$instance->isActive()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update step instance for inactive workflow'
                ], 422);
            }
            
            $stepInstance = WorkflowStepInstance::where('workflow_instance_id', $instanceId)
                ->where('id', $stepInstanceId)
                ->firstOrFail();
            
            // Check if step is in progress
            if (!$stepInstance->isInProgress()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update completed or skipped step instance'
                ], 422);
            }
            
            $validator = Validator::make($request->all(), [
                'assigned_to' => 'nullable|exists:users,id',
                'step_data' => 'nullable|json',
                'notes' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update step instance
            $updateData = [];
            
            if ($request->has('assigned_to')) {
                $updateData['assigned_to'] = $request->assigned_to;
            }
            
            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }
            
            if ($request->has('step_data')) {
                $updateData['step_data'] = array_merge($stepInstance->step_data ?? [], $request->step_data);
            }
            
            $stepInstance->update($updateData);
            
            return response()->json([
                'status' => 'success',
                'data' => $stepInstance->load(['workflowStep', 'assignee']),
                'message' => 'Workflow step instance updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating workflow step instance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update workflow step instance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create an approval for a step instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $instanceId
     * @param  int  $stepInstanceId
     * @return \Illuminate\Http\Response
     */
    public function createApproval(Request $request, $instanceId, $stepInstanceId)
    {
        try {
            $instance = WorkflowInstance::findOrFail($instanceId);
            
            // Check if workflow is active
            if (!$instance->isActive()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot create approval for inactive workflow'
                ], 422);
            }
            
            $stepInstance = WorkflowStepInstance::where('workflow_instance_id', $instanceId)
                ->where('id', $stepInstanceId)
                ->firstOrFail();
            
            // Check if step is in progress
            if (!$stepInstance->isInProgress()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot create approval for completed or skipped step instance'
                ], 422);
            }
            
            // Check if step is an approval step
            if (!$stepInstance->isApprovalStep()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This step is not configured as an approval step'
                ], 422);
            }
            
            $validator = Validator::make($request->all(), [
                'approval_type' => 'required|string|in:approve,reject',
                'comments' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if user has already approved/rejected
            $existingApproval = WorkflowApproval::where('workflow_step_instance_id', $stepInstanceId)
                ->where('approver_id', Auth::id())
                ->first();
                
            if ($existingApproval) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already submitted an approval decision for this step'
                ], 422);
            }
            
            // Create approval
            $approval = new WorkflowApproval([
                'workflow_step_instance_id' => $stepInstanceId,
                'approval_type' => 'user',
                'approver_id' => Auth::id(),
                'approver_role' => null, // Could be set based on user's role
                'status' => $request->approval_type === 'approve' ? 'approved' : 'rejected',
                'comments' => $request->comments,
                'responded_at' => now(),
            ]);
            
            $approval->save();
            
            return response()->json([
                'status' => 'success',
                'data' => $approval,
                'message' => 'Approval submitted successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating workflow approval: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get approvals for a step instance.
     *
     * @param  int  $instanceId
     * @param  int  $stepInstanceId
     * @return \Illuminate\Http\Response
     */
    public function getApprovals($instanceId, $stepInstanceId)
    {
        try {
            $instance = WorkflowInstance::findOrFail($instanceId);
            
            $stepInstance = WorkflowStepInstance::where('workflow_instance_id', $instanceId)
                ->where('id', $stepInstanceId)
                ->firstOrFail();
            
            $approvals = WorkflowApproval::with('approver')
                ->where('workflow_step_instance_id', $stepInstanceId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $approvals,
                'message' => 'Approvals retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving workflow approvals: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve approvals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current step instance for a workflow instance.
     *
     * @param  int  $instanceId
     * @return \Illuminate\Http\Response
     */
    public function getCurrentStep($instanceId)
    {
        try {
            $instance = WorkflowInstance::findOrFail($instanceId);
            
            $currentStepInstance = $instance->currentStepInstance();
            
            if (!$currentStepInstance) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No current step instance found'
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $currentStepInstance->load(['workflowStep', 'assignee', 'approvals']),
                'message' => 'Current step instance retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving current workflow step instance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve current step instance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}