<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Financial\RevenueCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RevenueCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $revenueCategories = RevenueCategory::all();
        return response()->json(['data' => $revenueCategories]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:revenue_categories',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $revenueCategory = RevenueCategory::create($request->all());
        return response()->json(['data' => $revenueCategory, 'message' => 'Revenue category created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $revenueCategory = RevenueCategory::with(['storageRevenueRates', 'handlingRevenueRates'])->findOrFail($id);
        return response()->json(['data' => $revenueCategory]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $revenueCategory = RevenueCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'code' => 'string|max:50|unique:revenue_categories,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $revenueCategory->update($request->all());
        return response()->json(['data' => $revenueCategory, 'message' => 'Revenue category updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $revenueCategory = RevenueCategory::findOrFail($id);
        
        // Check if there are any related records
        if ($revenueCategory->storageRevenueRates()->count() > 0 || 
            $revenueCategory->handlingRevenueRates()->count() > 0 || 
            $revenueCategory->revenueTransactions()->count() > 0) {
            return response()->json(['message' => 'Cannot delete category with related records'], 422);
        }
        
        $revenueCategory->delete();
        return response()->json(['message' => 'Revenue category deleted successfully']);
    }
}