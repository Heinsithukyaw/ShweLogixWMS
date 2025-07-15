<?php

namespace App\Http\Controllers\Api\Metrics;

use App\Http\Controllers\Controller;
use App\Models\Metrics\DashboardWidget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardWidgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DashboardWidget::with(['dashboard', 'metricVisualization']);
        
        // Filter by dashboard
        if ($request->has('dashboard_id')) {
            $query->where('dashboard_id', $request->dashboard_id);
        }
        
        $widgets = $query->get();
        return response()->json(['data' => $widgets]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dashboard_id' => 'required|exists:dashboards,id',
            'metric_visualization_id' => 'required|exists:metric_visualizations,id',
            'position_x' => 'required|integer|min:0',
            'position_y' => 'required|integer|min:0',
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $widget = DashboardWidget::create($request->all());
        return response()->json(['data' => $widget, 'message' => 'Dashboard widget created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $widget = DashboardWidget::with(['dashboard', 'metricVisualization.metricDefinition'])->findOrFail($id);
        return response()->json(['data' => $widget]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $widget = DashboardWidget::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'dashboard_id' => 'exists:dashboards,id',
            'metric_visualization_id' => 'exists:metric_visualizations,id',
            'position_x' => 'integer|min:0',
            'position_y' => 'integer|min:0',
            'width' => 'integer|min:1',
            'height' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $widget->update($request->all());
        return response()->json(['data' => $widget, 'message' => 'Dashboard widget updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $widget = DashboardWidget::findOrFail($id);
        $widget->delete();
        return response()->json(['message' => 'Dashboard widget deleted successfully']);
    }
}