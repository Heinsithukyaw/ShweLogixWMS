<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document\Document;
use App\Models\Document\DocumentVersion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentVersionController extends Controller
{
    /**
     * Display a listing of versions for a document.
     *
     * @param  int  $documentId
     * @return \Illuminate\Http\Response
     */
    public function index($documentId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to view the document
            if (!$this->checkPermission($document, 'view')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this document'
                ], 403);
            }
            
            $versions = DocumentVersion::with('creator')
                ->where('document_id', $documentId)
                ->orderBy('version_number', 'desc')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $versions,
                'message' => 'Document versions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving document versions: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve document versions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created version.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $documentId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $documentId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to edit the document
            if (!$this->checkPermission($document, 'edit')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to edit this document'
                ], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:10240', // 10MB max
                'change_notes' => 'nullable|string',
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
            
            // Get latest version number
            $latestVersion = $document->latestVersion;
            $versionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;
            
            // Create new version
            $version = new DocumentVersion([
                'document_id' => $documentId,
                'version_number' => $versionNumber,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'change_notes' => $request->change_notes ?? 'New version uploaded',
                'created_by' => Auth::id(),
            ]);
            
            $version->save();
            
            // Update document with new file info
            $document->update([
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $version->load('creator'),
                'message' => 'New version uploaded successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error uploading document version: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload new version',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified version.
     *
     * @param  int  $documentId
     * @param  int  $versionId
     * @return \Illuminate\Http\Response
     */
    public function show($documentId, $versionId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to view the document
            if (!$this->checkPermission($document, 'view')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this document'
                ], 403);
            }
            
            $version = DocumentVersion::with('creator')
                ->where('document_id', $documentId)
                ->where('id', $versionId)
                ->firstOrFail();
            
            return response()->json([
                'status' => 'success',
                'data' => $version,
                'message' => 'Document version retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving document version: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Document version not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified version (only change notes).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $documentId
     * @param  int  $versionId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $documentId, $versionId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to edit the document
            if (!$this->checkPermission($document, 'edit')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to edit this document'
                ], 403);
            }
            
            $version = DocumentVersion::where('document_id', $documentId)
                ->where('id', $versionId)
                ->firstOrFail();
            
            $validator = Validator::make($request->all(), [
                'change_notes' => 'required|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update change notes
            $version->update([
                'change_notes' => $request->change_notes,
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $version->load('creator'),
                'message' => 'Version notes updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating document version: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update version notes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a document to a previous version.
     *
     * @param  int  $documentId
     * @param  int  $versionId
     * @return \Illuminate\Http\Response
     */
    public function restore($documentId, $versionId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to edit the document
            if (!$this->checkPermission($document, 'edit')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to edit this document'
                ], 403);
            }
            
            $version = DocumentVersion::where('document_id', $documentId)
                ->where('id', $versionId)
                ->firstOrFail();
            
            // Get latest version number
            $latestVersion = $document->latestVersion;
            $versionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;
            
            // Create new version based on the old one
            $newVersion = new DocumentVersion([
                'document_id' => $documentId,
                'version_number' => $versionNumber,
                'file_name' => $version->file_name,
                'file_path' => $version->file_path, // Reference the same file
                'file_size' => $version->file_size,
                'mime_type' => $version->mime_type,
                'change_notes' => 'Restored from version ' . $version->version_number,
                'created_by' => Auth::id(),
            ]);
            
            $newVersion->save();
            
            // Update document with restored file info
            $document->update([
                'file_name' => $version->file_name,
                'file_path' => $version->file_path,
                'file_type' => pathinfo($version->file_name, PATHINFO_EXTENSION),
                'file_size' => $version->file_size,
                'mime_type' => $version->mime_type,
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'document' => $document,
                    'version' => $newVersion->load('creator')
                ],
                'message' => 'Document restored to previous version successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error restoring document version: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore document version',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a specific version of a document.
     *
     * @param  int  $documentId
     * @param  int  $versionId
     * @return \Illuminate\Http\Response
     */
    public function download($documentId, $versionId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to view the document
            if (!$this->checkPermission($document, 'view')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this document'
                ], 403);
            }
            
            $version = DocumentVersion::where('document_id', $documentId)
                ->where('id', $versionId)
                ->firstOrFail();
            
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
            Log::error('Error downloading document version: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download document version',
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