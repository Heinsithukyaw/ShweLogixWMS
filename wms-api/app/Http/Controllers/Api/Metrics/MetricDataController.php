<?php

namespace App\Http\Controllers\Api\Metrics;

use App\Http\Controllers\Controller;
use App\Models\Metrics\MetricData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MetricDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MetricData::with(['metricDefinition', 'warehouse', 'zone', 'businessParty']);
        
        // Filter by metric definition
        if ($request->has('metric_definition_id')) {
            $query->where('metric_definition_id', $request->metric_definition_id);
        }
        
        // Filter by warehouse
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('measurement_time', [$request->start_date, $request->end_date]);
        }
        
        $metricData = $query->get();
        return response()->json(['data' => $metricData]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'metric_definition_id' => 'required|exists:metric_definitions,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'zone_id' => 'nullable|exists:zones,id',
            'business_party_id' => 'nullable|exists:business_parties,id',
            'value' => 'required|numeric',
            'measurement_time' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $metricData = MetricData::create($request->all());
        return response()->json(['data' => $metricData, 'message' => 'Metric data created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $metricData = MetricData::with(['metricDefinition', 'warehouse', 'zone', 'businessParty'])->findOrFail($id);
        return response()->json(['data' => $metricData]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $metricData = MetricData::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'metric_definition_id' => 'exists:metric_definitions,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'zone_id' => 'nullable|exists:zones,id',
            'business_party_id' => 'nullable|exists:business_parties,id',
            'value' => 'numeric',
            'measurement_time' => 'date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $metricData->update($request->all());
        return response()->json(['data' => $metricData, 'message' => 'Metric data updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $metricData = MetricData::findOrFail($id);
        $metricData->delete();
        return response()->json(['message' => 'Metric data deleted successfully']);
    }
}