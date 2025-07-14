import api from './api';

// Routing API
export const routingApi = {
  getSuggestions: (params?: any) => api.get('/decision-support/routing/suggestions', { params }),
  optimize: (data: any) => api.post('/decision-support/routing/optimize', data),
  getPerformance: (params?: any) => api.get('/decision-support/routing/performance', { params }),
};

// Slotting API
export const slottingApi = {
  getRecommendations: (params?: any) => api.get('/decision-support/slotting/recommendations', { params }),
  applyRecommendations: (data: any) => api.post('/decision-support/slotting/apply', data),
  getAnalysis: (params?: any) => api.get('/decision-support/slotting/analysis', { params }),
};

// Labor API
export const laborApi = {
  getAllocation: (params?: any) => api.get('/decision-support/labor/allocation', { params }),
  optimize: (data: any) => api.post('/decision-support/labor/optimize', data),
  getEfficiency: (params?: any) => api.get('/decision-support/labor/efficiency', { params }),
};

// Equipment API
export const equipmentApi = {
  getUtilization: (params?: any) => api.get('/decision-support/equipment/utilization', { params }),
  getRecommendations: (params?: any) => api.get('/decision-support/equipment/recommendations', { params }),
  scheduleMaintenance: (data: any) => api.post('/decision-support/equipment/schedule', data),
};

// Decision Support Dashboard API
export const decisionSupportApi = {
  getDashboard: () => api.get('/decision-support/dashboard'),
  getAlerts: () => api.get('/decision-support/alerts'),
  approveDecision: (id: string) => api.post(`/decision-support/decisions/${id}/approve`),
  rejectDecision: (id: string) => api.post(`/decision-support/decisions/${id}/reject`),
};