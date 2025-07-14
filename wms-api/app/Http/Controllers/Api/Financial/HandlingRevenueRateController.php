<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Financial\HandlingRevenueRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HandlingRevenueRateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $handlingRevenueRates = HandlingRevenueRate::with(['revenueCategory', 'warehouse', 'businessParty'])->get();
        return response()->json(['data' => $handlingRevenueRates]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'revenue_category_id' => 'required|exists:revenue_categories,id',
            'activity_type' => 'required|string|in:receiving,putaway,picking,packing,shipping',
            'rate_per_unit' => 'required|numeric|min:0',
            'unit_type' => 'required|string|in:per_item,per_carton,per_pallet,per_order',
            'warehouse_id' => 'required|exists:warehouses,id',
            'business_party_id' => 'nullable|exists:business_parties,id',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $handlingRevenueRate = HandlingRevenueRate::create($request->all());
        return response()->json(['data' => $handlingRevenueRate, 'message' => 'Handling revenue rate created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $handlingRevenueRate = HandlingRevenueRate::with(['revenueCategory', 'warehouse', 'businessParty'])->findOrFail($id);
        return response()->json(['data' => $handlingRevenueRate]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $handlingRevenueRate = HandlingRevenueRate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'revenue_category_id' => 'exists:revenue_categories,id',
            'activity_type' => 'string|in:receiving,putaway,picking,packing,shipping',
            'rate_per_unit' => 'numeric|min:0',
            'unit_type' => 'string|in:per_item,per_carton,per_pallet,per_order',
            'warehouse_id' => 'exists:warehouses,id',
            'business_party_id' => 'nullable|exists:business_parties,id',
            'effective_date' => 'date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $handlingRevenueRate->update($request->all());
        return response()->json(['data' => $handlingRevenueRate, 'message' => 'Handling revenue rate updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $handlingRevenueRate = HandlingRevenueRate::findOrFail($id);
        $handlingRevenueRate->delete();
        return response()->json(['message' => 'Handling revenue rate deleted successfully']);
    }
}