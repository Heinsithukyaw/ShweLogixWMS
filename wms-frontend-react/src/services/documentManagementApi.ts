import { apiClient } from './apiClient';

// Document Types
export interface Document {
  id: number;
  document_number: string;
  title: string;
  description?: string;
  file_name: string;
  file_type: string;
  file_size: number;
  file_path: string;
  version: number;
  status: 'active' | 'archived' | 'deleted';
  category_id: number;
  category_name?: string;
  tags?: string[];
  created_by: number;
  created_at: string;
  updated_by?: number;
  updated_at?: string;
  access_level: 'public' | 'restricted' | 'private';
  expiration_date?: string;
  is_favorite?: boolean;
}

export interface DocumentCategory {
  id: number;
  name: string;
  description?: string;
  parent_id?: number;
  level: number;
  path?: string;
  document_count?: number;
  created_by: number;
  created_at: string;
  updated_by?: number;
  updated_at?: string;
}

export interface DocumentVersion {
  id: number;
  document_id: number;
  version: number;
  file_name: string;
  file_size: number;
  file_path: string;
  changes?: string;
  created_by: number;
  created_at: string;
}

export interface DocumentPermission {
  id: number;
  document_id: number;
  user_id?: number;
  user_group_id?: number;
  permission_type: 'view' | 'edit' | 'delete' | 'share';
  granted_by: number;
  granted_at: string;
  expires_at?: string;
}

export interface DocumentComment {
  id: number;
  document_id: number;
  user_id: number;
  user_name?: string;
  comment: string;
  created_at: string;
  updated_at?: string;
}

// Document Management API
export const documentApi = {
  // Get all documents
  getDocuments: (params?: any) => 
    apiClient.get('/documents', { params }),

  // Get document by ID
  getDocument: (id: number) => 
    apiClient.get(`/documents/${id}`),

  // Create new document
  createDocument: (formData: FormData) => 
    apiClient.post('/documents', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    }),

  // Update document
  updateDocument: (id: number, data: Partial<Document>) => 
    apiClient.put(`/documents/${id}`, data),

  // Delete document
  deleteDocument: (id: number) => 
    apiClient.delete(`/documents/${id}`),

  // Archive document
  archiveDocument: (id: number) => 
    apiClient.post(`/documents/${id}/archive`),

  // Restore document
  restoreDocument: (id: number) => 
    apiClient.post(`/documents/${id}/restore`),

  // Download document
  downloadDocument: (id: number) => 
    apiClient.get(`/documents/${id}/download`, { responseType: 'blob' }),

  // Get document versions
  getDocumentVersions: (documentId: number) => 
    apiClient.get(`/documents/${documentId}/versions`),

  // Get specific document version
  getDocumentVersion: (documentId: number, versionId: number) => 
    apiClient.get(`/documents/${documentId}/versions/${versionId}`),

  // Upload new document version
  uploadNewVersion: (documentId: number, formData: FormData) => 
    apiClient.post(`/documents/${documentId}/versions`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    }),

  // Revert to previous version
  revertToPreviousVersion: (documentId: number, versionId: number) => 
    apiClient.post(`/documents/${documentId}/versions/${versionId}/revert`),

  // Get document permissions
  getDocumentPermissions: (documentId: number) => 
    apiClient.get(`/documents/${documentId}/permissions`),

  // Grant document permission
  grantPermission: (documentId: number, data: {
    user_id?: number;
    user_group_id?: number;
    permission_type: 'view' | 'edit' | 'delete' | 'share';
    expires_at?: string;
  }) => 
    apiClient.post(`/documents/${documentId}/permissions`, data),

  // Revoke document permission
  revokePermission: (documentId: number, permissionId: number) => 
    apiClient.delete(`/documents/${documentId}/permissions/${permissionId}`),

  // Get document comments
  getDocumentComments: (documentId: number) => 
    apiClient.get(`/documents/${documentId}/comments`),

  // Add document comment
  addComment: (documentId: number, data: { comment: string }) => 
    apiClient.post(`/documents/${documentId}/comments`, data),

  // Update document comment
  updateComment: (documentId: number, commentId: number, data: { comment: string }) => 
    apiClient.put(`/documents/${documentId}/comments/${commentId}`, data),

  // Delete document comment
  deleteComment: (documentId: number, commentId: number) => 
    apiClient.delete(`/documents/${documentId}/comments/${commentId}`),

  // Search documents
  searchDocuments: (params: {
    query: string;
    category_id?: number;
    tags?: string[];
    date_from?: string;
    date_to?: string;
    file_type?: string[];
  }) => 
    apiClient.get('/documents/search', { params }),

  // Get recent documents
  getRecentDocuments: (limit: number = 10) => 
    apiClient.get('/documents/recent', { params: { limit } }),

  // Get favorite documents
  getFavoriteDocuments: () => 
    apiClient.get('/documents/favorites'),

  // Toggle favorite status
  toggleFavorite: (documentId: number) => 
    apiClient.post(`/documents/${documentId}/favorite`),
};

// Document Categories API
export const documentCategoryApi = {
  // Get all categories
  getCategories: (params?: any) => 
    apiClient.get('/document-categories', { params }),

  // Get category by ID
  getCategory: (id: number) => 
    apiClient.get(`/document-categories/${id}`),

  // Create new category
  createCategory: (data: {
    name: string;
    description?: string;
    parent_id?: number;
  }) => 
    apiClient.post('/document-categories', data),

  // Update category
  updateCategory: (id: number, data: Partial<DocumentCategory>) => 
    apiClient.put(`/document-categories/${id}`, data),

  // Delete category
  deleteCategory: (id: number) => 
    apiClient.delete(`/document-categories/${id}`),

  // Get category hierarchy
  getCategoryHierarchy: () => 
    apiClient.get('/document-categories/hierarchy'),

  // Get documents in category
  getCategoryDocuments: (categoryId: number, params?: any) => 
    apiClient.get(`/document-categories/${categoryId}/documents`, { params }),
};

// Document Tags API
export const documentTagApi = {
  // Get all tags
  getTags: () => 
    apiClient.get('/document-tags'),

  // Create new tag
  createTag: (data: { name: string }) => 
    apiClient.post('/document-tags', data),

  // Delete tag
  deleteTag: (id: number) => 
    apiClient.delete(`/document-tags/${id}`),

  // Get documents by tag
  getDocumentsByTag: (tagName: string, params?: any) => 
    apiClient.get(`/document-tags/${tagName}/documents`, { params }),
};