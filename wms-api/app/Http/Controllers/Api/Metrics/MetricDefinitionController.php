<?php

namespace App\Http\Controllers\Api\Metrics;

use App\Http\Controllers\Controller;
use App\Models\Metrics\MetricDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MetricDefinitionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $metricDefinitions = MetricDefinition::all();
        return response()->json(['data' => $metricDefinitions]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:metric_definitions',
            'description' => 'required|string',
            'category' => 'required|string|in:inbound,inventory,outbound,performance',
            'unit_of_measure' => 'required|string|max:50',
            'calculation_formula' => 'nullable|string',
            'data_source' => 'nullable|string',
            'frequency' => 'required|string|in:real-time,hourly,daily,weekly,monthly',
            'is_kpi' => 'boolean',
            'target_value' => 'nullable|numeric',
            'threshold_warning' => 'nullable|numeric',
            'threshold_critical' => 'nullable|numeric',
            'higher_is_better' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $metricDefinition = MetricDefinition::create($request->all());
        return response()->json(['data' => $metricDefinition, 'message' => 'Metric definition created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $metricDefinition = MetricDefinition::with('metricData')->findOrFail($id);
        return response()->json(['data' => $metricDefinition]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $metricDefinition = MetricDefinition::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'code' => 'string|max:50|unique:metric_definitions,code,' . $id,
            'description' => 'string',
            'category' => 'string|in:inbound,inventory,outbound,performance',
            'unit_of_measure' => 'string|max:50',
            'calculation_formula' => 'nullable|string',
            'data_source' => 'nullable|string',
            'frequency' => 'string|in:real-time,hourly,daily,weekly,monthly',
            'is_kpi' => 'boolean',
            'target_value' => 'nullable|numeric',
            'threshold_warning' => 'nullable|numeric',
            'threshold_critical' => 'nullable|numeric',
            'higher_is_better' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $metricDefinition->update($request->all());
        return response()->json(['data' => $metricDefinition, 'message' => 'Metric definition updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $metricDefinition = MetricDefinition::findOrFail($id);
        
        // Check if there are any related records
        if ($metricDefinition->metricData()->count() > 0) {
            return response()->json(['message' => 'Cannot delete metric definition with related data'], 422);
        }
        
        $metricDefinition->delete();
        return response()->json(['message' => 'Metric definition deleted successfully']);
    }
}