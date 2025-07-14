<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document\Document;
use App\Models\Document\DocumentShare;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class DocumentShareController extends Controller
{
    /**
     * Display a listing of shares for a document.
     *
     * @param  int  $documentId
     * @return \Illuminate\Http\Response
     */
    public function index($documentId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to view/edit the document
            if (!$this->canManageShares($document)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to manage shares for this document'
                ], 403);
            }
            
            $shares = DocumentShare::where('document_id', $documentId)
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $shares,
                'message' => 'Document shares retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving document shares: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve document shares',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new share link for a document.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $documentId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $documentId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to share the document
            if (!$this->canManageShares($document)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to share this document'
                ], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'shared_with' => 'nullable|string|max:255',
                'share_notes' => 'nullable|string',
                'expires_at' => 'nullable|date|after:now',
                'is_password_protected' => 'boolean',
                'password' => 'required_if:is_password_protected,true|nullable|string|min:6',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Create share link
            $share = new DocumentShare([
                'document_id' => $documentId,
                'share_token' => Str::uuid(),
                'shared_by' => Auth::id(),
                'shared_with' => $request->shared_with,
                'share_notes' => $request->share_notes,
                'expires_at' => $request->expires_at,
                'is_password_protected' => $request->is_password_protected ?? false,
                'is_active' => true,
            ]);
            
            // Hash password if provided
            if ($request->is_password_protected && $request->password) {
                $share->password_hash = Hash::make($request->password);
            }
            
            $share->save();
            
            // Generate share URL
            $shareUrl = url("/api/documents/shared/{$share->share_token}");
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'share' => $share,
                    'share_url' => $shareUrl
                ],
                'message' => 'Document shared successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error sharing document: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to share document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a share link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $documentId
     * @param  int  $shareId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $documentId, $shareId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to manage shares
            if (!$this->canManageShares($document)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to manage shares for this document'
                ], 403);
            }
            
            $share = DocumentShare::where('document_id', $documentId)
                ->where('id', $shareId)
                ->firstOrFail();
            
            $validator = Validator::make($request->all(), [
                'shared_with' => 'nullable|string|max:255',
                'share_notes' => 'nullable|string',
                'expires_at' => 'nullable|date|after:now',
                'is_password_protected' => 'boolean',
                'password' => 'nullable|string|min:6',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update share
            $share->fill($request->except('password'));
            
            // Update password if provided
            if ($request->has('is_password_protected')) {
                $share->is_password_protected = $request->is_password_protected;
                
                if ($request->is_password_protected && $request->password) {
                    $share->password_hash = Hash::make($request->password);
                } elseif (!$request->is_password_protected) {
                    $share->password_hash = null;
                }
            }
            
            $share->save();
            
            // Generate share URL
            $shareUrl = url("/api/documents/shared/{$share->share_token}");
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'share' => $share,
                    'share_url' => $shareUrl
                ],
                'message' => 'Share updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating document share: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update share',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a share link.
     *
     * @param  int  $documentId
     * @param  int  $shareId
     * @return \Illuminate\Http\Response
     */
    public function destroy($documentId, $shareId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to manage shares
            if (!$this->canManageShares($document)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to manage shares for this document'
                ], 403);
            }
            
            $share = DocumentShare::where('document_id', $documentId)
                ->where('id', $shareId)
                ->firstOrFail();
            
            $share->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Share removed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting document share: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove share',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Access a shared document using a share token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function accessShared(Request $request, $token)
    {
        try {
            $share = DocumentShare::where('share_token', $token)
                ->where('is_active', true)
                ->firstOrFail();
            
            // Check if share has expired
            if ($share->isExpired()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This share link has expired'
                ], 403);
            }
            
            // Check if password is required
            if ($share->is_password_protected) {
                if (!$request->has('password')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Password required',
                        'requires_password' => true
                    ], 401);
                }
                
                if (!$share->verifyPassword($request->password)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid password',
                        'requires_password' => true
                    ], 401);
                }
            }
            
            // Get document
            $document = $share->document()->with(['category', 'latestVersion'])->first();
            
            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document not found'
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'document' => $document,
                    'share' => $share
                ],
                'message' => 'Shared document accessed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error accessing shared document: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired share link',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Download a shared document using a share token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function downloadShared(Request $request, $token)
    {
        try {
            $share = DocumentShare::where('share_token', $token)
                ->where('is_active', true)
                ->firstOrFail();
            
            // Check if share has expired
            if ($share->isExpired()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This share link has expired'
                ], 403);
            }
            
            // Check if password is required
            if ($share->is_password_protected) {
                if (!$request->has('password')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Password required',
                        'requires_password' => true
                    ], 401);
                }
                
                if (!$share->verifyPassword($request->password)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid password',
                        'requires_password' => true
                    ], 401);
                }
            }
            
            // Get document
            $document = $share->document;
            
            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document not found'
                ], 404);
            }
            
            $version = $document->latestVersion;
            
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
            Log::error('Error downloading shared document: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired share link',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Check if the authenticated user can manage shares for the document.
     *
     * @param  \App\Models\Document\Document  $document
     * @return bool
     */
    private function canManageShares($document)
    {
        $user = Auth::user();
        
        // Admin users can manage all documents
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Document uploader can manage shares
        if ($document->uploaded_by === $user->id) {
            return true;
        }
        
        // Check if user has edit permission
        $userPermission = $document->permissions()
            ->where('user_id', $user->id)
            ->first();
            
        if ($userPermission && $userPermission->can_edit) {
            return true;
        }
        
        return false;
    }
}