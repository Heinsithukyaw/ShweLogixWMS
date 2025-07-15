<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Financial\BillingRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BillingRateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $billingRates = BillingRate::with('businessParty')->get();
        return response()->json(['data' => $billingRates]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:billing_rates',
            'business_party_id' => 'nullable|exists:business_parties,id',
            'service_type' => 'required|string|max:50',
            'rate' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'minimum_charge' => 'nullable|numeric|min:0',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $billingRate = BillingRate::create($request->all());
        return response()->json(['data' => $billingRate, 'message' => 'Billing rate created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $billingRate = BillingRate::with('businessParty')->findOrFail($id);
        return response()->json(['data' => $billingRate]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $billingRate = BillingRate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'code' => 'string|max:50|unique:billing_rates,code,' . $id,
            'business_party_id' => 'nullable|exists:business_parties,id',
            'service_type' => 'string|max:50',
            'rate' => 'numeric|min:0',
            'unit' => 'string|max:50',
            'minimum_charge' => 'nullable|numeric|min:0',
            'effective_date' => 'date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $billingRate->update($request->all());
        return response()->json(['data' => $billingRate, 'message' => 'Billing rate updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $billingRate = BillingRate::findOrFail($id);
        $billingRate->delete();
        return response()->json(['message' => 'Billing rate deleted successfully']);
    }
}