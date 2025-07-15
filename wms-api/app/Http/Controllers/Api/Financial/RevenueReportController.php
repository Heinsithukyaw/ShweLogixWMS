<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Financial\RevenueReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RevenueReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $revenueReports = RevenueReport::with(['businessParty', 'warehouse'])->get();
        return response()->json(['data' => $revenueReports]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|string|in:daily,weekly,monthly,quarterly,yearly',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'storage_revenue' => 'required|numeric|min:0',
            'handling_revenue' => 'required|numeric|min:0',
            'value_added_revenue' => 'required|numeric|min:0',
            'other_revenue' => 'required|numeric|min:0',
            'business_party_id' => 'nullable|exists:business_parties,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'status' => 'required|string|in:draft,final',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $revenueReport = RevenueReport::create($request->all());
        return response()->json(['data' => $revenueReport, 'message' => 'Revenue report created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $revenueReport = RevenueReport::with(['businessParty', 'warehouse'])->findOrFail($id);
        return response()->json(['data' => $revenueReport]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $revenueReport = RevenueReport::findOrFail($id);

        // Prevent updating finalized reports
        if ($revenueReport->status === 'final' && !$request->has('status')) {
            return response()->json(['message' => 'Cannot update a finalized report'], 422);
        }

        $validator = Validator::make($request->all(), [
            'report_type' => 'string|in:daily,weekly,monthly,quarterly,yearly',
            'period_start' => 'date',
            'period_end' => 'date|after_or_equal:period_start',
            'storage_revenue' => 'numeric|min:0',
            'handling_revenue' => 'numeric|min:0',
            'value_added_revenue' => 'numeric|min:0',
            'other_revenue' => 'numeric|min:0',
            'business_party_id' => 'nullable|exists:business_parties,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'status' => 'string|in:draft,final',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $revenueReport->update($request->all());
        return response()->json(['data' => $revenueReport, 'message' => 'Revenue report updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $revenueReport = RevenueReport::findOrFail($id);
        
        // Prevent deleting finalized reports
        if ($revenueReport->status === 'final') {
            return response()->json(['message' => 'Cannot delete a finalized report'], 422);
        }
        
        $revenueReport->delete();
        return response()->json(['message' => 'Revenue report deleted successfully']);
    }
}