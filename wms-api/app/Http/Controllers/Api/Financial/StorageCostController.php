<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Financial\StorageCost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StorageCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $storageCosts = StorageCost::with(['warehouse', 'zone', 'location'])->get();
        return response()->json(['data' => $storageCosts]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'zone_id' => 'nullable|exists:zones,id',
            'location_id' => 'nullable|exists:locations,id',
            'cost_per_unit' => 'required|numeric|min:0',
            'unit_type' => 'required|string|in:sqft,cbm,pallet_position',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $storageCost = StorageCost::create($request->all());
        return response()->json(['data' => $storageCost, 'message' => 'Storage cost created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $storageCost = StorageCost::with(['warehouse', 'zone', 'location'])->findOrFail($id);
        return response()->json(['data' => $storageCost]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $storageCost = StorageCost::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'exists:warehouses,id',
            'zone_id' => 'nullable|exists:zones,id',
            'location_id' => 'nullable|exists:locations,id',
            'cost_per_unit' => 'numeric|min:0',
            'unit_type' => 'string|in:sqft,cbm,pallet_position',
            'effective_date' => 'date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $storageCost->update($request->all());
        return response()->json(['data' => $storageCost, 'message' => 'Storage cost updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $storageCost = StorageCost::findOrFail($id);
        $storageCost->delete();
        return response()->json(['message' => 'Storage cost deleted successfully']);
    }
}