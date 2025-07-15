import api from './api';

// Demand Forecast API
export const demandForecastApi = {
  getAll: (params?: any) => api.get('/predictive-analytics/demand-forecast', { params }),
  getById: (id: string) => api.get(`/predictive-analytics/demand-forecast/${id}`),
  create: (data: any) => api.post('/predictive-analytics/demand-forecast', data),
  update: (id: string, data: any) => api.put(`/predictive-analytics/demand-forecast/${id}`, data),
  delete: (id: string) => api.delete(`/predictive-analytics/demand-forecast/${id}`),
  
  generateForecast: (data: any) => api.post('/predictive-analytics/demand-forecast/generate', data),
  getAvailableModels: () => api.get('/predictive-analytics/demand-forecast/models/available'),
  getAccuracyReport: (params?: any) => 
    api.get('/predictive-analytics/demand-forecast/accuracy/report', { params }),
  updateActualDemand: (id: string, actualDemand: number) => 
    api.post(`/predictive-analytics/demand-forecast/${id}/update-actual`, { actual_demand: actualDemand }),
};

// Cost Optimization API
export const costOptimizationApi = {
  getModels: () => api.get('/predictive-analytics/cost-optimization/models'),
  runOptimization: (data: any) => api.post('/predictive-analytics/cost-optimization/run', data),
  getResults: (id: string) => api.get(`/predictive-analytics/cost-optimization/results/${id}`),
};

// Layout Optimization API
export const layoutOptimizationApi = {
  analyze: (data: any) => api.post('/predictive-analytics/layout-optimization/analyze', data),
  getSuggestions: (warehouseId: string) => 
    api.get(`/predictive-analytics/layout-optimization/suggestions/${warehouseId}`),
};

// Performance Prediction API
export const performancePredictionApi = {
  predict: (data: any) => api.post('/predictive-analytics/performance-prediction/predict', data),
  getHistorical: (entityType: string, entityId: string) => 
    api.get(`/predictive-analytics/performance-prediction/historical/${entityType}/${entityId}`),
};

// AI Models API
export const aiModelsApi = {
  getAll: () => api.get('/ai-models'),
  train: (data: any) => api.post('/ai-models/train', data),
  getTrainingStatus: (id: string) => api.get(`/ai-models/training/${id}/status`),
};