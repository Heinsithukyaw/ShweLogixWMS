import api from './api';

// Roles API
export const rolesApi = {
  getAll: (params?: any) => api.get('/user-management/roles', { params }),
  getById: (id: string) => api.get(`/user-management/roles/${id}`),
  create: (data: any) => api.post('/user-management/roles', data),
  update: (id: string, data: any) => api.put(`/user-management/roles/${id}`, data),
  delete: (id: string) => api.delete(`/user-management/roles/${id}`),
  assignPermissions: (id: string, permissions: string[]) => 
    api.post(`/user-management/roles/${id}/permissions`, { permissions }),
  revokePermission: (id: string, permissionId: string) => 
    api.delete(`/user-management/roles/${id}/permissions/${permissionId}`),
  getUsers: (id: string) => api.get(`/user-management/roles/${id}/users`),
};

// Permissions API
export const permissionsApi = {
  getAll: (params?: any) => api.get('/user-management/permissions', { params }),
  getById: (id: string) => api.get(`/user-management/permissions/${id}`),
  create: (data: any) => api.post('/user-management/permissions', data),
  update: (id: string, data: any) => api.put(`/user-management/permissions/${id}`, data),
  delete: (id: string) => api.delete(`/user-management/permissions/${id}`),
  getModules: () => api.get('/user-management/permissions/modules/list'),
  getCategories: () => api.get('/user-management/permissions/categories/list'),
};

// Tenants API
export const tenantsApi = {
  getAll: (params?: any) => api.get('/user-management/tenants', { params }),
  getById: (id: string) => api.get(`/user-management/tenants/${id}`),
  create: (data: any) => api.post('/user-management/tenants', data),
  update: (id: string, data: any) => api.put(`/user-management/tenants/${id}`, data),
  delete: (id: string) => api.delete(`/user-management/tenants/${id}`),
  activate: (id: string) => api.post(`/user-management/tenants/${id}/activate`),
  deactivate: (id: string) => api.post(`/user-management/tenants/${id}/deactivate`),
  getUsers: (id: string) => api.get(`/user-management/tenants/${id}/users`),
  getStatistics: (id: string) => api.get(`/user-management/tenants/${id}/statistics`),
};

// User Activity Logs API
export const userActivityApi = {
  getAll: (params?: any) => api.get('/user-management/activity-logs', { params }),
  getUserActivity: (userId: string, params?: any) => 
    api.get(`/user-management/activity-logs/user/${userId}`, { params }),
  getStatistics: (params?: any) => api.get('/user-management/activity-logs/statistics', { params }),
  export: (params?: any) => api.get('/user-management/activity-logs/export', { params }),
};