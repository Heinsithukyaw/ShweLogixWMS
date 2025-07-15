import { apiClient } from './apiClient';

// Workflow Types
export interface Workflow {
  id: number;
  name: string;
  description?: string;
  version: number;
  status: 'draft' | 'active' | 'inactive' | 'archived';
  category: string;
  trigger_type: 'manual' | 'event' | 'scheduled';
  trigger_config?: any;
  created_by: number;
  created_at: string;
  updated_by?: number;
  updated_at?: string;
  is_template?: boolean;
  execution_count?: number;
  average_duration?: number;
  success_rate?: number;
}

export interface WorkflowStep {
  id: number;
  workflow_id: number;
  step_number: number;
  name: string;
  description?: string;
  step_type: 'task' | 'approval' | 'condition' | 'notification' | 'integration' | 'delay' | 'subprocess';
  config: any;
  next_step_id?: number;
  alternative_step_id?: number;
  condition?: string;
  timeout_minutes?: number;
  timeout_action?: 'continue' | 'alternative' | 'fail';
  is_required: boolean;
  created_by: number;
  created_at: string;
  updated_by?: number;
  updated_at?: string;
}

export interface WorkflowExecution {
  id: number;
  workflow_id: number;
  workflow_version: number;
  status: 'pending' | 'in_progress' | 'completed' | 'failed' | 'cancelled';
  started_at: string;
  completed_at?: string;
  initiated_by: number;
  input_data?: any;
  output_data?: any;
  current_step_id?: number;
  error_message?: string;
  execution_time_seconds?: number;
}

export interface WorkflowStepExecution {
  id: number;
  workflow_execution_id: number;
  step_id: number;
  status: 'pending' | 'in_progress' | 'completed' | 'failed' | 'skipped';
  started_at?: string;
  completed_at?: string;
  assigned_to?: number;
  input_data?: any;
  output_data?: any;
  notes?: string;
  error_message?: string;
  execution_time_seconds?: number;
}

export interface WorkflowApproval {
  id: number;
  step_execution_id: number;
  approver_id: number;
  status: 'pending' | 'approved' | 'rejected';
  comments?: string;
  requested_at: string;
  responded_at?: string;
}

// Workflow Management API
export const workflowApi = {
  // Get all workflows
  getWorkflows: (params?: any) => 
    apiClient.get('/workflows', { params }),

  // Get workflow by ID
  getWorkflow: (id: number) => 
    apiClient.get(`/workflows/${id}`),

  // Create new workflow
  createWorkflow: (data: {
    name: string;
    description?: string;
    category: string;
    trigger_type: 'manual' | 'event' | 'scheduled';
    trigger_config?: any;
    is_template?: boolean;
  }) => 
    apiClient.post('/workflows', data),

  // Update workflow
  updateWorkflow: (id: number, data: Partial<Workflow>) => 
    apiClient.put(`/workflows/${id}`, data),

  // Delete workflow
  deleteWorkflow: (id: number) => 
    apiClient.delete(`/workflows/${id}`),

  // Activate workflow
  activateWorkflow: (id: number) => 
    apiClient.post(`/workflows/${id}/activate`),

  // Deactivate workflow
  deactivateWorkflow: (id: number) => 
    apiClient.post(`/workflows/${id}/deactivate`),

  // Archive workflow
  archiveWorkflow: (id: number) => 
    apiClient.post(`/workflows/${id}/archive`),

  // Clone workflow
  cloneWorkflow: (id: number, data: { name: string }) => 
    apiClient.post(`/workflows/${id}/clone`, data),

  // Export workflow
  exportWorkflow: (id: number) => 
    apiClient.get(`/workflows/${id}/export`),

  // Import workflow
  importWorkflow: (data: FormData) => 
    apiClient.post('/workflows/import', data, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    }),

  // Get workflow categories
  getCategories: () => 
    apiClient.get('/workflows/categories'),

  // Create workflow category
  createCategory: (data: { name: string; description?: string }) => 
    apiClient.post('/workflows/categories', data),

  // Get workflow templates
  getTemplates: (params?: any) => 
    apiClient.get('/workflows/templates', { params }),

  // Create workflow from template
  createFromTemplate: (templateId: number, data: { name: string }) => 
    apiClient.post(`/workflows/templates/${templateId}/create`, data),
};

// Workflow Steps API
export const workflowStepApi = {
  // Get workflow steps
  getWorkflowSteps: (workflowId: number) => 
    apiClient.get(`/workflows/${workflowId}/steps`),

  // Get step by ID
  getStep: (workflowId: number, stepId: number) => 
    apiClient.get(`/workflows/${workflowId}/steps/${stepId}`),

  // Create step
  createStep: (workflowId: number, data: {
    name: string;
    description?: string;
    step_type: string;
    config: any;
    step_number: number;
    next_step_id?: number;
    alternative_step_id?: number;
    condition?: string;
    timeout_minutes?: number;
    timeout_action?: string;
    is_required: boolean;
  }) => 
    apiClient.post(`/workflows/${workflowId}/steps`, data),

  // Update step
  updateStep: (workflowId: number, stepId: number, data: Partial<WorkflowStep>) => 
    apiClient.put(`/workflows/${workflowId}/steps/${stepId}`, data),

  // Delete step
  deleteStep: (workflowId: number, stepId: number) => 
    apiClient.delete(`/workflows/${workflowId}/steps/${stepId}`),

  // Reorder steps
  reorderSteps: (workflowId: number, data: { step_ids: number[] }) => 
    apiClient.post(`/workflows/${workflowId}/steps/reorder`, data),

  // Get step types
  getStepTypes: () => 
    apiClient.get('/workflows/step-types'),

  // Get step type config schema
  getStepTypeSchema: (stepType: string) => 
    apiClient.get(`/workflows/step-types/${stepType}/schema`),
};

// Workflow Execution API
export const workflowExecutionApi = {
  // Get workflow executions
  getExecutions: (params?: any) => 
    apiClient.get('/workflow-executions', { params }),

  // Get workflow executions for a specific workflow
  getWorkflowExecutions: (workflowId: number, params?: any) => 
    apiClient.get(`/workflows/${workflowId}/executions`, { params }),

  // Get execution by ID
  getExecution: (id: number) => 
    apiClient.get(`/workflow-executions/${id}`),

  // Start workflow execution
  startExecution: (workflowId: number, data: { input_data?: any }) => 
    apiClient.post(`/workflows/${workflowId}/execute`, data),

  // Cancel execution
  cancelExecution: (id: number) => 
    apiClient.post(`/workflow-executions/${id}/cancel`),

  // Get execution steps
  getExecutionSteps: (executionId: number) => 
    apiClient.get(`/workflow-executions/${executionId}/steps`),

  // Get execution step by ID
  getExecutionStep: (executionId: number, stepId: number) => 
    apiClient.get(`/workflow-executions/${executionId}/steps/${stepId}`),

  // Complete manual step
  completeManualStep: (executionId: number, stepExecutionId: number, data: {
    output_data?: any;
    notes?: string;
  }) => 
    apiClient.post(`/workflow-executions/${executionId}/steps/${stepExecutionId}/complete`, data),

  // Fail manual step
  failManualStep: (executionId: number, stepExecutionId: number, data: {
    error_message: string;
    notes?: string;
  }) => 
    apiClient.post(`/workflow-executions/${executionId}/steps/${stepExecutionId}/fail`, data),
};

// Workflow Approval API
export const workflowApprovalApi = {
  // Get pending approvals for current user
  getPendingApprovals: (params?: any) => 
    apiClient.get('/workflow-approvals/pending', { params }),

  // Get approval by ID
  getApproval: (id: number) => 
    apiClient.get(`/workflow-approvals/${id}`),

  // Approve request
  approveRequest: (id: number, data: { comments?: string }) => 
    apiClient.post(`/workflow-approvals/${id}/approve`, data),

  // Reject request
  rejectRequest: (id: number, data: { comments: string }) => 
    apiClient.post(`/workflow-approvals/${id}/reject`, data),

  // Reassign approval
  reassignApproval: (id: number, data: { approver_id: number }) => 
    apiClient.post(`/workflow-approvals/${id}/reassign`, data),
};

// Workflow Analytics API
export const workflowAnalyticsApi = {
  // Get workflow performance metrics
  getPerformanceMetrics: (params?: any) => 
    apiClient.get('/workflow-analytics/performance', { params }),

  // Get workflow execution trends
  getExecutionTrends: (params?: any) => 
    apiClient.get('/workflow-analytics/execution-trends', { params }),

  // Get bottleneck analysis
  getBottleneckAnalysis: (workflowId: number) => 
    apiClient.get(`/workflow-analytics/bottlenecks/${workflowId}`),

  // Get approval time analysis
  getApprovalTimeAnalysis: (params?: any) => 
    apiClient.get('/workflow-analytics/approval-times', { params }),
};