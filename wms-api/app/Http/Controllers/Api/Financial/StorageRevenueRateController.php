<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Financial\StorageRevenueRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StorageRevenueRateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $storageRevenueRates = StorageRevenueRate::with(['revenueCategory', 'warehouse', 'zone'])->get();
        return response()->json(['data' => $storageRevenueRates]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'revenue_category_id' => 'required|exists:revenue_categories,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'zone_id' => 'nullable|exists:zones,id',
            'rate_per_unit' => 'required|numeric|min:0',
            'unit_type' => 'required|string|in:sqft,cbm,pallet_position',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $storageRevenueRate = StorageRevenueRate::create($request->all());
        return response()->json(['data' => $storageRevenueRate, 'message' => 'Storage revenue rate created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $storageRevenueRate = StorageRevenueRate::with(['revenueCategory', 'warehouse', 'zone'])->findOrFail($id);
        return response()->json(['data' => $storageRevenueRate]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $storageRevenueRate = StorageRevenueRate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'revenue_category_id' => 'exists:revenue_categories,id',
            'warehouse_id' => 'exists:warehouses,id',
            'zone_id' => 'nullable|exists:zones,id',
            'rate_per_unit' => 'numeric|min:0',
            'unit_type' => 'string|in:sqft,cbm,pallet_position',
            'effective_date' => 'date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $storageRevenueRate->update($request->all());
        return response()->json(['data' => $storageRevenueRate, 'message' => 'Storage revenue rate updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $storageRevenueRate = StorageRevenueRate::findOrFail($id);
        $storageRevenueRate->delete();
        return response()->json(['message' => 'Storage revenue rate deleted successfully']);
    }
}