import api from './api';

// Layout Simulation API
export const layoutSimulationApi = {
  // Simulation Management
  getAll: (params?: any) => api.get('/layout-simulation', { params }),
  getById: (id: string) => api.get(`/layout-simulation/${id}`),
  create: (data: any) => api.post('/layout-simulation', data),
  update: (id: string, data: any) => api.put(`/layout-simulation/${id}`, data),
  delete: (id: string) => api.delete(`/layout-simulation/${id}`),
  
  // Element Management
  addElement: (id: string, data: any) => api.post(`/layout-simulation/${id}/elements`, data),
  updateElement: (id: string, elementId: string, data: any) => 
    api.put(`/layout-simulation/${id}/elements/${elementId}`, data),
  removeElement: (id: string, elementId: string) => 
    api.delete(`/layout-simulation/${id}/elements/${elementId}`),
  moveElement: (id: string, elementId: string, data: any) => 
    api.post(`/layout-simulation/${id}/elements/${elementId}/move`, data),
  resizeElement: (id: string, elementId: string, data: any) => 
    api.post(`/layout-simulation/${id}/elements/${elementId}/resize`, data),
  
  // Simulation Execution
  runSimulation: (id: string, parameters?: any) => 
    api.post(`/layout-simulation/${id}/simulate`, { parameters }),
  getKPIPredictions: (id: string) => api.get(`/layout-simulation/${id}/kpi-predictions`),
  getPerformanceMetrics: (id: string) => api.get(`/layout-simulation/${id}/performance-metrics`),
  
  // Scenario Management
  createScenario: (id: string, data: any) => api.post(`/layout-simulation/${id}/scenarios`, data),
  getScenarios: (id: string) => api.get(`/layout-simulation/${id}/scenarios`),
  compareLayouts: (data: any) => api.post('/layout-simulation/compare', data),
  getComparison: (comparisonId: string) => api.get(`/layout-simulation/comparison/${comparisonId}`),
  
  // Save/Load Operations
  saveLayout: (id: string) => api.post(`/layout-simulation/${id}/save`),
  loadLayout: (id: string, data: any) => api.post(`/layout-simulation/${id}/load`, data),
  exportLayout: (id: string) => api.post(`/layout-simulation/${id}/export`),
  importLayout: (data: any) => api.post('/layout-simulation/import', data),
  
  // Templates
  getTemplates: () => api.get('/layout-simulation/templates'),
  createTemplate: (data: any) => api.post('/layout-simulation/templates', data),
};