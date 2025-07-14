<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Financial\BudgetVsActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BudgetVsActualController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $budgetVsActual = BudgetVsActual::with('costCategory')->get();
        return response()->json(['data' => $budgetVsActual]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cost_category_id' => 'required|exists:cost_categories,id',
            'budgeted_amount' => 'required|numeric|min:0',
            'actual_amount' => 'required|numeric|min:0',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'period_type' => 'required|string|in:monthly,quarterly,yearly',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $budgetVsActual = BudgetVsActual::create($request->all());
        return response()->json(['data' => $budgetVsActual, 'message' => 'Budget vs Actual record created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $budgetVsActual = BudgetVsActual::with('costCategory')->findOrFail($id);
        return response()->json(['data' => $budgetVsActual]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $budgetVsActual = BudgetVsActual::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'cost_category_id' => 'exists:cost_categories,id',
            'budgeted_amount' => 'numeric|min:0',
            'actual_amount' => 'numeric|min:0',
            'period_start' => 'date',
            'period_end' => 'date|after_or_equal:period_start',
            'period_type' => 'string|in:monthly,quarterly,yearly',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $budgetVsActual->update($request->all());
        return response()->json(['data' => $budgetVsActual, 'message' => 'Budget vs Actual record updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $budgetVsActual = BudgetVsActual::findOrFail($id);
        $budgetVsActual->delete();
        return response()->json(['message' => 'Budget vs Actual record deleted successfully']);
    }
}