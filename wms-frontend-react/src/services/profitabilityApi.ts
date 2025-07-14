import api from './api';

// Profitability Analysis API
export const profitabilityApi = {
  // Overview and Display
  getOverview: (params?: any) => api.get('/profitability/overview', { params }),
  getMarginDisplay: () => api.get('/profitability/margin-display'),
  
  // Monthly Charts
  getMonthlyCharts: (params?: any) => api.get('/profitability/monthly-charts', { params }),
  getTrends: (params?: any) => api.get('/profitability/trends', { params }),
  
  // Client Profitability
  getClientProfitability: (params?: any) => api.get('/profitability/clients', { params }),
  getClientDetails: (clientId: string) => api.get(`/profitability/clients/${clientId}`),
  analyzeClient: (clientId: string, data: any) => 
    api.post(`/profitability/clients/${clientId}/analyze`, data),
  
  // Cost Allocation
  getAllocationMethods: () => api.get('/profitability/cost-allocation/methods'),
  traditionalAllocation: (data: any) => api.post('/profitability/cost-allocation/traditional', data),
  abcAllocation: (data: any) => api.post('/profitability/cost-allocation/abc', data),
  compareAllocationMethods: (params?: any) => 
    api.get('/profitability/cost-allocation/comparison', { params }),
  
  // Analysis Management
  getAll: (params?: any) => api.get('/profitability', { params }),
  getById: (id: string) => api.get(`/profitability/${id}`),
  create: (data: any) => api.post('/profitability', data),
  update: (id: string, data: any) => api.put(`/profitability/${id}`, data),
  delete: (id: string) => api.delete(`/profitability/${id}`),
  recalculate: (id: string) => api.post(`/profitability/${id}/recalculate`),
};