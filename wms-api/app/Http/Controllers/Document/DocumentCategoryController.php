<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document\DocumentCategory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DocumentCategoryController extends Controller
{
    /**
     * Display a listing of document categories.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = DocumentCategory::query();
            
            // Apply filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $categories = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $categories,
                'message' => 'Document categories retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving document categories: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve document categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created document category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:document_categories',
                'description' => 'nullable|string',
                'metadata_schema' => 'nullable|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $category = DocumentCategory::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $category,
                'message' => 'Document category created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating document category: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create document category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified document category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $category = DocumentCategory::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $category,
                'message' => 'Document category retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving document category: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Document category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified document category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $category = DocumentCategory::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'code' => 'string|max:50|unique:document_categories,code,' . $id,
                'description' => 'nullable|string',
                'metadata_schema' => 'nullable|json',
                'is_active' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $category->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $category,
                'message' => 'Document category updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating document category: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update document category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified document category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $category = DocumentCategory::findOrFail($id);
            
            // Check if there are documents using this category
            if ($category->documents()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete category with associated documents'
                ], 422);
            }
            
            $category->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Document category deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting document category: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete document category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}