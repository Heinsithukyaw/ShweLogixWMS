<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document\Document;
use App\Models\Document\DocumentPermission;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DocumentPermissionController extends Controller
{
    /**
     * Display a listing of permissions for a document.
     *
     * @param  int  $documentId
     * @return \Illuminate\Http\Response
     */
    public function index($documentId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to view/edit the document
            if (!$this->canManagePermissions($document)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to manage this document'
                ], 403);
            }
            
            $permissions = DocumentPermission::with(['user', 'role'])
                ->where('document_id', $documentId)
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $permissions,
                'message' => 'Document permissions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving document permissions: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve document permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created permission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $documentId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $documentId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to manage permissions
            if (!$this->canManagePermissions($document)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to manage this document'
                ], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'user_id' => 'required_without:role_id|exists:users,id|nullable',
                'role_id' => 'required_without:user_id|exists:roles,id|nullable',
                'can_view' => 'required|boolean',
                'can_edit' => 'required|boolean',
                'can_delete' => 'required|boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if permission already exists
            $existingPermission = DocumentPermission::where('document_id', $documentId)
                ->where(function($query) use ($request) {
                    if ($request->user_id) {
                        $query->where('user_id', $request->user_id);
                    } else {
                        $query->where('role_id', $request->role_id);
                    }
                })
                ->first();
                
            if ($existingPermission) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Permission already exists for this user/role'
                ], 422);
            }
            
            // Create new permission
            $permission = new DocumentPermission([
                'document_id' => $documentId,
                'user_id' => $request->user_id,
                'role_id' => $request->role_id,
                'can_view' => $request->can_view,
                'can_edit' => $request->can_edit,
                'can_delete' => $request->can_delete,
            ]);
            
            $permission->save();
            
            return response()->json([
                'status' => 'success',
                'data' => $permission->load(['user', 'role']),
                'message' => 'Permission granted successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating document permission: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified permission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $documentId
     * @param  int  $permissionId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $documentId, $permissionId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to manage permissions
            if (!$this->canManagePermissions($document)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to manage this document'
                ], 403);
            }
            
            $permission = DocumentPermission::where('document_id', $documentId)
                ->where('id', $permissionId)
                ->firstOrFail();
            
            $validator = Validator::make($request->all(), [
                'can_view' => 'boolean',
                'can_edit' => 'boolean',
                'can_delete' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update permission
            $permission->update($request->only(['can_view', 'can_edit', 'can_delete']));
            
            return response()->json([
                'status' => 'success',
                'data' => $permission->load(['user', 'role']),
                'message' => 'Permission updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating document permission: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified permission.
     *
     * @param  int  $documentId
     * @param  int  $permissionId
     * @return \Illuminate\Http\Response
     */
    public function destroy($documentId, $permissionId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to manage permissions
            if (!$this->canManagePermissions($document)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to manage this document'
                ], 403);
            }
            
            $permission = DocumentPermission::where('document_id', $documentId)
                ->where('id', $permissionId)
                ->firstOrFail();
            
            // Prevent removing permission for document owner
            if ($permission->user_id === $document->uploaded_by) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot remove permission for document owner'
                ], 422);
            }
            
            $permission->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Permission removed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting document permission: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if the authenticated user can manage permissions for the document.
     *
     * @param  \App\Models\Document\Document  $document
     * @return bool
     */
    private function canManagePermissions($document)
    {
        $user = Auth::user();
        
        // Admin users can manage all documents
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Document uploader can manage permissions
        if ($document->uploaded_by === $user->id) {
            return true;
        }
        
        return false;
    }
}