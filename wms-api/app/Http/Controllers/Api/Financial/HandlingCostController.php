<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Financial\HandlingCost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HandlingCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $handlingCosts = HandlingCost::with('warehouse')->get();
        return response()->json(['data' => $handlingCosts]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'activity_type' => 'required|string|in:receiving,putaway,picking,packing,shipping',
            'cost_per_unit' => 'required|numeric|min:0',
            'unit_type' => 'required|string|in:per_item,per_carton,per_pallet,per_order',
            'warehouse_id' => 'required|exists:warehouses,id',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $handlingCost = HandlingCost::create($request->all());
        return response()->json(['data' => $handlingCost, 'message' => 'Handling cost created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $handlingCost = HandlingCost::with('warehouse')->findOrFail($id);
        return response()->json(['data' => $handlingCost]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $handlingCost = HandlingCost::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'activity_type' => 'string|in:receiving,putaway,picking,packing,shipping',
            'cost_per_unit' => 'numeric|min:0',
            'unit_type' => 'string|in:per_item,per_carton,per_pallet,per_order',
            'warehouse_id' => 'exists:warehouses,id',
            'effective_date' => 'date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $handlingCost->update($request->all());
        return response()->json(['data' => $handlingCost, 'message' => 'Handling cost updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $handlingCost = HandlingCost::findOrFail($id);
        $handlingCost->delete();
        return response()->json(['message' => 'Handling cost deleted successfully']);
    }
}