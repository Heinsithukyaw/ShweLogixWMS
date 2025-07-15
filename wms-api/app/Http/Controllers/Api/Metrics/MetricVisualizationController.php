<?php

namespace App\Http\Controllers\Api\Metrics;

use App\Http\Controllers\Controller;
use App\Models\Metrics\MetricVisualization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MetricVisualizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MetricVisualization::with('metricDefinition');
        
        // Filter by metric definition
        if ($request->has('metric_definition_id')) {
            $query->where('metric_definition_id', $request->metric_definition_id);
        }
        
        $visualizations = $query->get();
        return response()->json(['data' => $visualizations]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:line,bar,pie,gauge,table,card',
            'configuration' => 'required|json',
            'metric_definition_id' => 'required|exists:metric_definitions,id',
            'time_range' => 'required|string|in:day,week,month,quarter,year,custom',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $visualization = MetricVisualization::create($request->all());
        return response()->json(['data' => $visualization, 'message' => 'Metric visualization created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $visualization = MetricVisualization::with(['metricDefinition', 'dashboardWidgets'])->findOrFail($id);
        return response()->json(['data' => $visualization]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $visualization = MetricVisualization::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'type' => 'string|in:line,bar,pie,gauge,table,card',
            'configuration' => 'json',
            'metric_definition_id' => 'exists:metric_definitions,id',
            'time_range' => 'string|in:day,week,month,quarter,year,custom',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $visualization->update($request->all());
        return response()->json(['data' => $visualization, 'message' => 'Metric visualization updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $visualization = MetricVisualization::findOrFail($id);
        
        // Check if there are any dashboard widgets using this visualization
        if ($visualization->dashboardWidgets()->count() > 0) {
            return response()->json(['message' => 'Cannot delete visualization that is used in dashboards'], 422);
        }
        
        $visualization->delete();
        return response()->json(['message' => 'Metric visualization deleted successfully']);
    }
}