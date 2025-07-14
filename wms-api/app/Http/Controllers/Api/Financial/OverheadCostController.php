<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Financial\OverheadCost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OverheadCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $overheadCosts = OverheadCost::with('costCategory')->get();
        return response()->json(['data' => $overheadCosts]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cost_category_id' => 'required|exists:cost_categories,id',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|string|in:monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $overheadCost = OverheadCost::create($request->all());
        return response()->json(['data' => $overheadCost, 'message' => 'Overhead cost created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $overheadCost = OverheadCost::with('costCategory')->findOrFail($id);
        return response()->json(['data' => $overheadCost]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $overheadCost = OverheadCost::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'cost_category_id' => 'exists:cost_categories,id',
            'amount' => 'numeric|min:0',
            'frequency' => 'string|in:monthly,quarterly,yearly',
            'start_date' => 'date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $overheadCost->update($request->all());
        return response()->json(['data' => $overheadCost, 'message' => 'Overhead cost updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $overheadCost = OverheadCost::findOrFail($id);
        $overheadCost->delete();
        return response()->json(['message' => 'Overhead cost deleted successfully']);
    }
}