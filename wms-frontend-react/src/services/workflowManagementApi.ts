import { apiClient } from './apiClient';

// Workflow Types
export interface WorkflowDefinition {
  id: number;
  name: string;
  description?: string;
  category: string;
  version: number;
  status: 'draft' | 'active' | 'inactive' | 'archived';
  trigger_type: 'manual' | 'automatic' | 'scheduled' | 'event';
  trigger_conditions?: any;
  steps: WorkflowStep[];
  created_by: number;
  created_at: string;
  updated_by?: number;
  updated_at?: string;
  is_template?: boolean;
  tags?: string[];
}

export interface WorkflowStep {
  id: number;
  workflow_id: number;
  step_number: number;
  name: string;
  description?: string;
  step_type: 'approval' | 'notification' | 'task' | 'condition' | 'script' | 'integration';
  configuration: any;
  assigned_to?: number;
  assigned_group?: number;
  timeout_hours?: number;
  is_required: boolean;
  parallel_execution?: boolean;
  conditions?: any;
  created_at: string;
  updated_at?: string;
}

export interface WorkflowInstance {
  id: number;
  workflow_definition_id: number;
  workflow_name?: string;
  instance_name?: string;
  status: 'pending' | 'in_progress' | 'completed' | 'failed' | 'cancelled' | 'paused';
  current_step?: number;
  initiated_by: number;
  initiated_at: string;
  completed_at?: string;
  context_data?: any;
  priority: 'low' | 'normal' | 'high' | 'urgent';
  due_date?: string;
  notes?: string;
}

export interface WorkflowStepExecution {
  id: number;
  workflow_instance_id: number;
  workflow_step_id: number;
  step_name?: string;
  status: 'pending' | 'in_progress' | 'completed' | 'failed' | 'skipped' | 'cancelled';
  assigned_to?: number;
  started_at?: string;
  completed_at?: string;
  result_data?: any;
  comments?: string;
  execution_time_seconds?: number;
}

export interface WorkflowApproval {
  id: number;
  workflow_instance_id: number;
  workflow_step_execution_id: number;
  approver_id: number;
  approval_status: 'pending' | 'approved' | 'rejected' | 'delegated';
  approval_date?: string;
  comments?: string;
  delegated_to?: number;
  is_required: boolean;
}

export interface WorkflowTemplate {
  id: number;
  name: string;
  description?: string;
  category: string;
  template_data: any;
  is_public: boolean;
  created_by: number;
  created_at: string;
  usage_count?: number;
}

// Workflow Definition API
export const workflowDefinitionApi = {
  // Get all workflow definitions
  getWorkflowDefinitions: (params?: any) => 
    apiClient.get('/workflows/definitions', { params }),

  // Get workflow definition by ID
  getWorkflowDefinition: (id: number) => 
    apiClient.get(`/workflows/definitions/${id}`),

  // Create new workflow definition
  createWorkflowDefinition: (data: {
    name: string;
    description?: string;
    category: string;
    trigger_type: string;
    trigger_conditions?: any;
    steps: Partial<WorkflowStep>[];
    tags?: string[];
  }) => 
    apiClient.post('/workflows/definitions', data),

  // Update workflow definition
  updateWorkflowDefinition: (id: number, data: Partial<WorkflowDefinition>) => 
    apiClient.put(`/workflows/definitions/${id}`, data),

  // Delete workflow definition
  deleteWorkflowDefinition: (id: number) => 
    apiClient.delete(`/workflows/definitions/${id}`),

  // Activate workflow definition
  activateWorkflowDefinition: (id: number) => 
    apiClient.post(`/workflows/definitions/${id}/activate`),

  // Deactivate workflow definition
  deactivateWorkflowDefinition: (id: number) => 
    apiClient.post(`/workflows/definitions/${id}/deactivate`),

  // Clone workflow definition
  cloneWorkflowDefinition: (id: number, data: { name: string; description?: string }) => 
    apiClient.post(`/workflows/definitions/${id}/clone`, data),

  // Get workflow definition steps
  getWorkflowSteps: (workflowId: number) => 
    apiClient.get(`/workflows/definitions/${workflowId}/steps`),

  // Add step to workflow
  addWorkflowStep: (workflowId: number, data: Partial<WorkflowStep>) => 
    apiClient.post(`/workflows/definitions/${workflowId}/steps`, data),

  // Update workflow step
  updateWorkflowStep: (workflowId: number, stepId: number, data: Partial<WorkflowStep>) => 
    apiClient.put(`/workflows/definitions/${workflowId}/steps/${stepId}`, data),

  // Delete workflow step
  deleteWorkflowStep: (workflowId: number, stepId: number) => 
    apiClient.delete(`/workflows/definitions/${workflowId}/steps/${stepId}`),

  // Reorder workflow steps
  reorderWorkflowSteps: (workflowId: number, data: { step_orders: { step_id: number; step_number: number }[] }) => 
    apiClient.post(`/workflows/definitions/${workflowId}/steps/reorder`, data),

  // Validate workflow definition
  validateWorkflowDefinition: (id: number) => 
    apiClient.post(`/workflows/definitions/${id}/validate`),

  // Export workflow definition
  exportWorkflowDefinition: (id: number) => 
    apiClient.get(`/workflows/definitions/${id}/export`),

  // Import workflow definition
  importWorkflowDefinition: (data: any) => 
    apiClient.post('/workflows/definitions/import', data),
};

// Workflow Instance API
export const workflowInstanceApi = {
  // Get all workflow instances
  getWorkflowInstances: (params?: any) => 
    apiClient.get('/workflows/instances', { params }),

  // Get workflow instance by ID
  getWorkflowInstance: (id: number) => 
    apiClient.get(`/workflows/instances/${id}`),

  // Start new workflow instance
  startWorkflowInstance: (data: {
    workflow_definition_id: number;
    instance_name?: string;
    context_data?: any;
    priority?: string;
    due_date?: string;
    notes?: string;
  }) => 
    apiClient.post('/workflows/instances', data),

  // Update workflow instance
  updateWorkflowInstance: (id: number, data: Partial<WorkflowInstance>) => 
    apiClient.put(`/workflows/instances/${id}`, data),

  // Cancel workflow instance
  cancelWorkflowInstance: (id: number, reason?: string) => 
    apiClient.post(`/workflows/instances/${id}/cancel`, { reason }),

  // Pause workflow instance
  pauseWorkflowInstance: (id: number) => 
    apiClient.post(`/workflows/instances/${id}/pause`),

  // Resume workflow instance
  resumeWorkflowInstance: (id: number) => 
    apiClient.post(`/workflows/instances/${id}/resume`),

  // Get workflow instance steps
  getWorkflowInstanceSteps: (instanceId: number) => 
    apiClient.get(`/workflows/instances/${instanceId}/steps`),

  // Get workflow instance history
  getWorkflowInstanceHistory: (instanceId: number) => 
    apiClient.get(`/workflows/instances/${instanceId}/history`),

  // Get workflow instance timeline
  getWorkflowInstanceTimeline: (instanceId: number) => 
    apiClient.get(`/workflows/instances/${instanceId}/timeline`),

  // Retry failed workflow instance
  retryWorkflowInstance: (id: number) => 
    apiClient.post(`/workflows/instances/${id}/retry`),
};

// Workflow Step Execution API
export const workflowStepExecutionApi = {
  // Get step execution details
  getStepExecution: (id: number) => 
    apiClient.get(`/workflows/step-executions/${id}`),

  // Complete step execution
  completeStepExecution: (id: number, data: {
    result_data?: any;
    comments?: string;
  }) => 
    apiClient.post(`/workflows/step-executions/${id}/complete`, data),

  // Fail step execution
  failStepExecution: (id: number, data: {
    error_message: string;
    error_details?: any;
  }) => 
    apiClient.post(`/workflows/step-executions/${id}/fail`, data),

  // Skip step execution
  skipStepExecution: (id: number, reason: string) => 
    apiClient.post(`/workflows/step-executions/${id}/skip`, { reason }),

  // Reassign step execution
  reassignStepExecution: (id: number, data: {
    assigned_to?: number;
    assigned_group?: number;
    reason?: string;
  }) => 
    apiClient.post(`/workflows/step-executions/${id}/reassign`, data),

  // Add comment to step execution
  addStepComment: (id: number, comment: string) => 
    apiClient.post(`/workflows/step-executions/${id}/comments`, { comment }),
};

// Workflow Approval API
export const workflowApprovalApi = {
  // Get pending approvals for user
  getPendingApprovals: (userId?: number) => 
    apiClient.get('/workflows/approvals/pending', { params: { user_id: userId } }),

  // Get approval details
  getApproval: (id: number) => 
    apiClient.get(`/workflows/approvals/${id}`),

  // Approve workflow step
  approveWorkflowStep: (id: number, data: {
    comments?: string;
    result_data?: any;
  }) => 
    apiClient.post(`/workflows/approvals/${id}/approve`, data),

  // Reject workflow step
  rejectWorkflowStep: (id: number, data: {
    comments: string;
    reason?: string;
  }) => 
    apiClient.post(`/workflows/approvals/${id}/reject`, data),

  // Delegate approval
  delegateApproval: (id: number, data: {
    delegated_to: number;
    comments?: string;
  }) => 
    apiClient.post(`/workflows/approvals/${id}/delegate`, data),

  // Get approval history
  getApprovalHistory: (workflowInstanceId: number) => 
    apiClient.get(`/workflows/instances/${workflowInstanceId}/approvals`),
};

// Workflow Template API
export const workflowTemplateApi = {
  // Get all workflow templates
  getWorkflowTemplates: (params?: any) => 
    apiClient.get('/workflows/templates', { params }),

  // Get workflow template by ID
  getWorkflowTemplate: (id: number) => 
    apiClient.get(`/workflows/templates/${id}`),

  // Create workflow template
  createWorkflowTemplate: (data: {
    name: string;
    description?: string;
    category: string;
    template_data: any;
    is_public?: boolean;
  }) => 
    apiClient.post('/workflows/templates', data),

  // Update workflow template
  updateWorkflowTemplate: (id: number, data: Partial<WorkflowTemplate>) => 
    apiClient.put(`/workflows/templates/${id}`, data),

  // Delete workflow template
  deleteWorkflowTemplate: (id: number) => 
    apiClient.delete(`/workflows/templates/${id}`),

  // Create workflow from template
  createWorkflowFromTemplate: (templateId: number, data: {
    name: string;
    description?: string;
    customizations?: any;
  }) => 
    apiClient.post(`/workflows/templates/${templateId}/create-workflow`, data),

  // Get popular templates
  getPopularTemplates: (limit: number = 10) => 
    apiClient.get('/workflows/templates/popular', { params: { limit } }),
};

// Workflow Analytics API
export const workflowAnalyticsApi = {
  // Get workflow performance metrics
  getWorkflowMetrics: (params?: any) => 
    apiClient.get('/workflows/analytics/metrics', { params }),

  // Get workflow completion rates
  getCompletionRates: (params?: any) => 
    apiClient.get('/workflows/analytics/completion-rates', { params }),

  // Get average execution times
  getExecutionTimes: (params?: any) => 
    apiClient.get('/workflows/analytics/execution-times', { params }),

  // Get bottleneck analysis
  getBottleneckAnalysis: (workflowId?: number) => 
    apiClient.get('/workflows/analytics/bottlenecks', { params: { workflow_id: workflowId } }),

  // Get user performance
  getUserPerformance: (userId: number, params?: any) => 
    apiClient.get(`/workflows/analytics/users/${userId}/performance`, { params }),

  // Get workflow dashboard data
  getDashboardData: (params?: any) => 
    apiClient.get('/workflows/analytics/dashboard', { params }),
};