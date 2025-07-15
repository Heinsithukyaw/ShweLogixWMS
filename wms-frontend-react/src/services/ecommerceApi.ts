import api from './api';

// Order Fulfillment API
export const orderFulfillmentApi = {
  getAll: (params?: any) => api.get('/ecommerce/fulfillment', { params }),
  getById: (id: string) => api.get(`/ecommerce/fulfillment/${id}`),
  create: (data: any) => api.post('/ecommerce/fulfillment', data),
  update: (id: string, data: any) => api.put(`/ecommerce/fulfillment/${id}`, data),
  processAutomation: (id: string) => api.post(`/ecommerce/fulfillment/${id}/process-automation`),
  updateStatus: (id: string, status: string) => api.post(`/ecommerce/fulfillment/${id}/update-status`, { status }),
  getAnalytics: () => api.get('/ecommerce/fulfillment/analytics/overview'),
  getPendingAutomation: () => api.get('/ecommerce/fulfillment/automation/pending'),
};

// Inventory Sync API
export const inventorySyncApi = {
  getAll: (params?: any) => api.get('/ecommerce/inventory-sync', { params }),
  getById: (id: string) => api.get(`/ecommerce/inventory-sync/${id}`),
  create: (data: any) => api.post('/ecommerce/inventory-sync', data),
  update: (id: string, data: any) => api.put(`/ecommerce/inventory-sync/${id}`, data),
  delete: (id: string) => api.delete(`/ecommerce/inventory-sync/${id}`),
  syncProduct: (id: string) => api.post(`/ecommerce/inventory-sync/${id}/sync`),
  syncAll: () => api.post('/ecommerce/inventory-sync/sync-all'),
  getStatistics: () => api.get('/ecommerce/inventory-sync/statistics/overview'),
  getFailedSyncs: () => api.get('/ecommerce/inventory-sync/failed/list'),
  retrySync: (id: string) => api.post(`/ecommerce/inventory-sync/${id}/retry`),
};

// Return Orders API
export const returnOrderApi = {
  getAll: (params?: any) => api.get('/ecommerce/returns', { params }),
  getById: (id: string) => api.get(`/ecommerce/returns/${id}`),
  create: (data: any) => api.post('/ecommerce/returns', data),
  update: (id: string, data: any) => api.put(`/ecommerce/returns/${id}`, data),
  approve: (id: string, data: any) => api.post(`/ecommerce/returns/${id}/approve`, data),
  receive: (id: string, data: any) => api.post(`/ecommerce/returns/${id}/receive`, data),
  process: (id: string, data: any) => api.post(`/ecommerce/returns/${id}/process`, data),
  getAnalytics: () => api.get('/ecommerce/returns/analytics/overview'),
  calculateRefund: (id: string) => api.get(`/ecommerce/returns/${id}/calculate-refund`),
};

// Mobile API
export const mobileApi = {
  getDashboard: () => api.get('/mobile/dashboard'),
  getTasks: () => api.get('/mobile/tasks'),
  scan: (data: { barcode: string; scan_type: string }) => api.post('/mobile/scan', data),
};