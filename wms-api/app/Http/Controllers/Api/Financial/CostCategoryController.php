<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Financial\CostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CostCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $costCategories = CostCategory::with('parent')->get();
        return response()->json(['data' => $costCategories]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cost_categories',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:cost_categories,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $costCategory = CostCategory::create($request->all());
        return response()->json(['data' => $costCategory, 'message' => 'Cost category created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $costCategory = CostCategory::with(['parent', 'children', 'overheadCosts'])->findOrFail($id);
        return response()->json(['data' => $costCategory]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $costCategory = CostCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'code' => 'string|max:50|unique:cost_categories,code,' . $id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:cost_categories,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $costCategory->update($request->all());
        return response()->json(['data' => $costCategory, 'message' => 'Cost category updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $costCategory = CostCategory::findOrFail($id);
        
        // Check if there are any child categories
        if ($costCategory->children()->count() > 0) {
            return response()->json(['message' => 'Cannot delete category with child categories'], 422);
        }
        
        $costCategory->delete();
        return response()->json(['message' => 'Cost category deleted successfully']);
    }
}