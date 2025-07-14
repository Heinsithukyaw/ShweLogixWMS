<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Financial\RevenueTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RevenueTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $revenueTransactions = RevenueTransaction::with(['revenueCategory', 'businessParty'])->get();
        return response()->json(['data' => $revenueTransactions]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'revenue_category_id' => 'required|exists:revenue_categories,id',
            'business_party_id' => 'required|exists:business_parties,id',
            'transaction_type' => 'required|string|in:storage,handling,value-added,other',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'invoice_number' => 'nullable|string|max:50',
            'payment_status' => 'required|string|in:pending,paid,cancelled',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $revenueTransaction = RevenueTransaction::create($request->all());
        return response()->json(['data' => $revenueTransaction, 'message' => 'Revenue transaction created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $revenueTransaction = RevenueTransaction::with(['revenueCategory', 'businessParty'])->findOrFail($id);
        return response()->json(['data' => $revenueTransaction]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $revenueTransaction = RevenueTransaction::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'revenue_category_id' => 'exists:revenue_categories,id',
            'business_party_id' => 'exists:business_parties,id',
            'transaction_type' => 'string|in:storage,handling,value-added,other',
            'amount' => 'numeric|min:0',
            'transaction_date' => 'date',
            'invoice_number' => 'nullable|string|max:50',
            'payment_status' => 'string|in:pending,paid,cancelled',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $revenueTransaction->update($request->all());
        return response()->json(['data' => $revenueTransaction, 'message' => 'Revenue transaction updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $revenueTransaction = RevenueTransaction::findOrFail($id);
        $revenueTransaction->delete();
        return response()->json(['message' => 'Revenue transaction deleted successfully']);
    }
}