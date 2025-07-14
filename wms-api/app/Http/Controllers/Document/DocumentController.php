<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document\Document;
use App\Models\Document\DocumentVersion;
use App\Models\Document\DocumentPermission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Display a listing of documents.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = Document::with(['category', 'uploader']);
            
            // Apply filters
            if ($request->has('document_category_id')) {
                $query->where('document_category_id', $request->document_category_id);
            }
            
            if ($request->has('reference_type')) {
                $query->where('reference_type', $request->reference_type);
            }
            
            if ($request->has('reference_id')) {
                $query->where('reference_id', $request->reference_id);
            }
            
            if ($request->has('uploaded_by')) {
                $query->where('uploaded_by', $request->uploaded_by);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('file_name', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $documents = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $documents,
                'message' => 'Documents retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving documents: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created document.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'document_category_id' => 'required|exists:document_categories,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file' => 'required|file|max:10240', // 10MB max
                'metadata' => 'nullable|json',
                'reference_type' => 'nullable|string|max:100',
                'reference_id' => 'nullable|string|max:100',
                'expires_at' => 'nullable|date',
                'is_confidential' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Handle file upload
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileType = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            
            // Generate a unique file path
            $filePath = 'documents/' . date('Y/m/d') . '/' . Str::uuid() . '.' . $fileType;
            
            // Store the file
            Storage::put($filePath, file_get_contents($file));
            
            // Create document record
            $document = new Document([
                'document_category_id' => $request->document_category_id,
                'title' => $request->title,
                'description' => $request->description,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'metadata' => $request->metadata,
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'uploaded_by' => Auth::id(),
                'expires_at' => $request->expires_at,
                'is_confidential' => $request->is_confidential ?? false,
            ]);
            
            $document->save();
            
            // Create initial version
            $version = new DocumentVersion([
                'document_id' => $document->id,
                'version_number' => 1,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'change_notes' => 'Initial version',
                'created_by' => Auth::id(),
            ]);
            
            $version->save();
            
            // Create default permission for uploader
            $permission = new DocumentPermission([
                'document_id' => $document->id,
                'user_id' => Auth::id(),
                'can_view' => true,
                'can_edit' => true,
                'can_delete' => true,
            ]);
            
            $permission->save();
            
            return response()->json([
                'status' => 'success',
                'data' => $document->load(['category', 'uploader', 'latestVersion']),
                'message' => 'Document uploaded successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified document.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $document = Document::with(['category', 'uploader', 'latestVersion', 'versions', 'permissions'])
                ->findOrFail($id);
            
            // Check if user has permission to view
            if (!$this->checkPermission($document, 'view')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this document'
                ], 403);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $document,
                'message' => 'Document retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving document: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified document.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            // Check if user has permission to edit
            if (!$this->checkPermission($document, 'edit')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to edit this document'
                ], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'document_category_id' => 'exists:document_categories,id',
                'title' => 'string|max:255',
                'description' => 'nullable|string',
                'file' => 'nullable|file|max:10240', // 10MB max
                'metadata' => 'nullable|json',
                'reference_type' => 'nullable|string|max:100',
                'reference_id' => 'nullable|string|max:100',
                'expires_at' => 'nullable|date',
                'is_confidential' => 'boolean',
                'change_notes' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update document metadata
            $document->fill($request->except('file', 'change_notes'));
            
            // Handle file update if provided
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = $file->getClientOriginalName();
                $fileType = $file->getClientOriginalExtension();
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();
                
                // Generate a unique file path
                $filePath = 'documents/' . date('Y/m/d') . '/' . Str::uuid() . '.' . $fileType;
                
                // Store the file
                Storage::put($filePath, file_get_contents($file));
                
                // Update document with new file info
                $document->file_name = $fileName;
                $document->file_path = $filePath;
                $document->file_type = $fileType;
                $document->file_size = $fileSize;
                $document->mime_type = $mimeType;
                
                // Create new version
                $latestVersion = $document->latestVersion;
                $versionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;
                
                $version = new DocumentVersion([
                    'document_id' => $document->id,
                    'version_number' => $versionNumber,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'change_notes' => $request->change_notes ?? 'Updated document',
                    'created_by' => Auth::id(),
                ]);
                
                $version->save();
            }
            
            $document->save();
            
            return response()->json([
                'status' => 'success',
                'data' => $document->load(['category', 'uploader', 'latestVersion']),
                'message' => 'Document updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating document: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified document.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $document = Document::findOrFail($id);
            
            // Check if user has permission to delete
            if (!$this->checkPermission($document, 'delete')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to delete this document'
                ], 403);
            }
            
            // Delete file versions
            foreach ($document->versions as $version) {
                if (Storage::exists($version->file_path)) {
                    Storage::delete($version->file_path);
                }
            }
            
            // Delete document and related records (versions, permissions, shares)
            $document->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Document deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting document: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download the document file.
     *
     * @param  int  $id
     * @param  int|null  $versionId
     * @return \Illuminate\Http\Response
     */
    public function download($id, $versionId = null)
    {
        try {
            $document = Document::findOrFail($id);
            
            // Check if user has permission to view
            if (!$this->checkPermission($document, 'view')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to download this document'
                ], 403);
            }
            
            // Get the requested version or latest version
            if ($versionId) {
                $version = DocumentVersion::where('document_id', $document->id)
                    ->where('id', $versionId)
                    ->firstOrFail();
            } else {
                $version = $document->latestVersion;
            }
            
            if (!$version) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document version not found'
                ], 404);
            }
            
            // Check if file exists
            if (!Storage::exists($version->file_path)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document file not found'
                ], 404);
            }
            
            // Return file for download
            return Storage::download(
                $version->file_path,
                $version->file_name,
                ['Content-Type' => $version->mime_type]
            );
        } catch (\Exception $e) {
            Log::error('Error downloading document: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if the authenticated user has the specified permission for the document.
     *
     * @param  \App\Models\Document\Document  $document
     * @param  string  $permission (view, edit, delete)
     * @return bool
     */
    private function checkPermission($document, $permission)
    {
        $user = Auth::user();
        
        // Admin users have all permissions
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Document uploader has all permissions
        if ($document->uploaded_by === $user->id) {
            return true;
        }
        
        // Check explicit permissions
        $userPermission = $document->permissions()
            ->where('user_id', $user->id)
            ->first();
            
        if ($userPermission) {
            switch ($permission) {
                case 'view':
                    return $userPermission->can_view;
                case 'edit':
                    return $userPermission->can_edit;
                case 'delete':
                    return $userPermission->can_delete;
                default:
                    return false;
            }
        }
        
        // Check role-based permissions
        $userRoles = $user->roles->pluck('id')->toArray();
        $rolePermission = $document->permissions()
            ->whereIn('role_id', $userRoles)
            ->first();
            
        if ($rolePermission) {
            switch ($permission) {
                case 'view':
                    return $rolePermission->can_view;
                case 'edit':
                    return $rolePermission->can_edit;
                case 'delete':
                    return $rolePermission->can_delete;
                default:
                    return false;
            }
        }
        
        return false;
    }
}