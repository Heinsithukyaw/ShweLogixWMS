<?php

namespace App\Http\Controllers\EDI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EDI\EdiDocumentType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EdiDocumentTypeController extends Controller
{
    /**
     * Display a listing of document types.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = EdiDocumentType::query();
            
            // Apply filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            if ($request->has('standard')) {
                $query->where('standard', $request->standard);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('document_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $documentTypes = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $documentTypes,
                'message' => 'Document types retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving document types: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve document types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created document type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'document_code' => 'required|string|max:50|unique:edi_document_types',
                'description' => 'nullable|string',
                'standard' => 'required|string|max:50',
                'version' => 'required|string|max:50',
                'direction' => 'required|string|in:inbound,outbound,both',
                'schema_definition' => 'nullable|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $documentType = EdiDocumentType::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $documentType,
                'message' => 'Document type created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating document type: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create document type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified document type.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $documentType = EdiDocumentType::with(['mappings', 'transactions'])
                ->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $documentType,
                'message' => 'Document type retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving document type: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Document type not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified document type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $documentType = EdiDocumentType::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'document_code' => 'string|max:50|unique:edi_document_types,document_code,' . $id,
                'description' => 'nullable|string',
                'standard' => 'string|max:50',
                'version' => 'string|max:50',
                'direction' => 'string|in:inbound,outbound,both',
                'schema_definition' => 'nullable|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $documentType->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $documentType,
                'message' => 'Document type updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating document type: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update document type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified document type.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $documentType = EdiDocumentType::findOrFail($id);
            
            // Check if there are related mappings or transactions
            if ($documentType->mappings()->count() > 0 || $documentType->transactions()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete document type with associated mappings or transactions'
                ], 422);
            }
            
            $documentType->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Document type deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting document type: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete document type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get document types by standard.
     *
     * @param  string  $standard
     * @return \Illuminate\Http\Response
     */
    public function getByStandard($standard)
    {
        try {
            $documentTypes = EdiDocumentType::where('standard', $standard)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $documentTypes,
                'message' => 'Document types retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving document types by standard: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve document types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate an EDI document against schema.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function validateDocument(Request $request, $id)
    {
        try {
            $documentType = EdiDocumentType::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'document_content' => 'required|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Implement document validation against schema
            $documentContent = $request->document_content;
            $schemaDefinition = $documentType->schema_definition;
            
            // This is a placeholder - actual implementation would use appropriate EDI validation library
            $isValid = true;
            $validationErrors = [];
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'is_valid' => $isValid,
                    'validation_errors' => $validationErrors
                ],
                'message' => $isValid ? 'Document is valid' : 'Document has validation errors'
            ]);
        } catch (\Exception $e) {
            Log::error('Error validating EDI document: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to validate document',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}