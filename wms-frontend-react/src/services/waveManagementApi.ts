import { apiClient } from './apiClient';
import { PickWave, WaveCreationParams } from '../type/outbound/picking';

// Wave Management API
export const waveManagementApi = {
  // Get all pick waves
  getWaves: (params?: any) => 
    apiClient.get('/outbound/picking/waves', { params }),

  // Create a new pick wave
  createWave: (data: WaveCreationParams) => 
    apiClient.post('/outbound/picking/waves', data),

  // Get wave details
  getWave: (id: number) => 
    apiClient.get(`/outbound/picking/waves/${id}`),

  // Update wave
  updateWave: (id: number, data: Partial<PickWave>) => 
    apiClient.put(`/outbound/picking/waves/${id}`, data),

  // Release wave for picking
  releaseWave: (id: number) => 
    apiClient.post(`/outbound/picking/waves/${id}/release`),

  // Cancel wave
  cancelWave: (id: number) => 
    apiClient.post(`/outbound/picking/waves/${id}/cancel`),

  // Get wave orders
  getWaveOrders: (waveId: number) => 
    apiClient.get(`/outbound/picking/waves/${waveId}/orders`),

  // Get wave items
  getWaveItems: (waveId: number) => 
    apiClient.get(`/outbound/picking/waves/${waveId}/items`),

  // Add orders to wave
  addOrdersToWave: (waveId: number, data: { order_ids: number[] }) => 
    apiClient.post(`/outbound/picking/waves/${waveId}/orders`, data),

  // Remove order from wave
  removeOrderFromWave: (waveId: number, orderId: number) => 
    apiClient.delete(`/outbound/picking/waves/${waveId}/orders/${orderId}`),

  // Assign employees to wave
  assignEmployees: (waveId: number, data: { employee_ids: number[] }) => 
    apiClient.post(`/outbound/picking/waves/${waveId}/assign`, data),

  // Generate pick lists for wave
  generatePickLists: (waveId: number, data: {
    pick_type: 'single' | 'batch' | 'zone' | 'cluster';
    pick_method: 'discrete' | 'batch' | 'zone' | 'cluster';
    batch_size?: number;
    zone_assignments?: any;
    optimization_rules?: any;
  }) => 
    apiClient.post(`/outbound/picking/waves/${waveId}/generate-lists`, data),

  // Get wave performance metrics
  getWavePerformance: (waveId: number) => 
    apiClient.get(`/outbound/picking/waves/${waveId}/performance`),

  // Get wave summary
  getWaveSummary: (params?: any) => 
    apiClient.get('/outbound/picking/waves/summary', { params }),

  // Get wave recommendations
  getWaveRecommendations: (params?: any) => 
    apiClient.get('/outbound/picking/waves/recommendations', { params }),
};

// Pick Task Management API
export const pickTaskApi = {
  // Get all pick tasks
  getTasks: (params?: any) => 
    apiClient.get('/outbound/picking/tasks', { params }),

  // Get task details
  getTask: (id: number) => 
    apiClient.get(`/outbound/picking/tasks/${id}`),

  // Assign task
  assignTask: (id: number, data: { employee_id: number }) => 
    apiClient.post(`/outbound/picking/tasks/${id}/assign`, data),

  // Start task
  startTask: (id: number) => 
    apiClient.post(`/outbound/picking/tasks/${id}/start`),

  // Complete task
  completeTask: (id: number) => 
    apiClient.post(`/outbound/picking/tasks/${id}/complete`),

  // Get task items
  getTaskItems: (taskId: number) => 
    apiClient.get(`/outbound/picking/tasks/${taskId}/items`),

  // Pick item
  pickItem: (taskId: number, itemId: number, data: {
    quantity_picked: number;
    confirmation_method: 'barcode' | 'manual' | 'voice';
    barcode_scanned?: string;
    lot_number?: string;
    serial_number?: string;
    notes?: string;
  }) => 
    apiClient.post(`/outbound/picking/tasks/${taskId}/items/${itemId}/pick`, data),

  // Report exception
  reportException: (taskId: number, itemId: number, data: {
    exception_type: 'inventory_shortage' | 'location_mismatch' | 'product_damage' | 'barcode_issue' | 'other';
    description: string;
  }) => 
    apiClient.post(`/outbound/picking/tasks/${taskId}/items/${itemId}/exception`, data),

  // Get employee performance
  getEmployeePerformance: (employeeId: number, params?: any) => 
    apiClient.get(`/outbound/picking/performance/employee/${employeeId}`, { params }),

  // Get task exceptions
  getTaskExceptions: (params?: any) => 
    apiClient.get('/outbound/picking/exceptions', { params }),

  // Resolve exception
  resolveException: (exceptionId: number, data: {
    resolution_notes: string;
  }) => 
    apiClient.post(`/outbound/picking/exceptions/${exceptionId}/resolve`, data),
};

// Mobile Picking API
export const mobilePickingApi = {
  // Get assigned tasks for employee
  getAssignedTasks: (employeeId: number) => 
    apiClient.get(`/mobile/outbound/picking/tasks/assigned/${employeeId}`),

  // Scan location
  scanLocation: (taskId: number, data: {
    location_barcode: string;
  }) => 
    apiClient.post(`/mobile/outbound/picking/tasks/${taskId}/scan-location`, data),

  // Scan product
  scanProduct: (taskId: number, data: {
    product_barcode: string;
    quantity: number;
  }) => 
    apiClient.post(`/mobile/outbound/picking/tasks/${taskId}/scan-product`, data),

  // Complete pick
  completePick: (taskId: number) => 
    apiClient.post(`/mobile/outbound/picking/tasks/${taskId}/complete`),
};