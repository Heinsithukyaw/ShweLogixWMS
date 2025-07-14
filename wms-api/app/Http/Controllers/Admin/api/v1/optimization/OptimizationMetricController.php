<?php

namespace App\Http\Controllers\Admin\api\v1\optimization;

use App\Http\Controllers\Controller;
use App\Models\OptimizationMetric;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OptimizationMetricController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $layoutId = $request->query('warehouse_layout_id');
        $metricType = $request->query('metric_type');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $query = OptimizationMetric::query();
        
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        
        if ($layoutId) {
            $query->where('warehouse_layout_id', $layoutId);
        }
        
        if ($metricType) {
            $query->where('metric_type', $metricType);
        }
        
        if ($startDate) {
            $query->where('measured_at', '>=', Carbon::parse($startDate));
        }
        
        if ($endDate) {
            $query->where('measured_at', '<=', Carbon::parse($endDate));
        }
        
        $metrics = $query->with(['warehouse', 'warehouseLayout'])->paginate(20);
        
        return response()->json([
            'status' => 'success',
            'data' => $metrics
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'warehouse_layout_id' => 'nullable|exists:warehouse_layouts,id',
            'metric_type' => 'required|string|in:space_utilization,travel_distance,throughput,picking_efficiency',
            'value' => 'required|numeric',
            'details' => 'nullable|json',
            'measured_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        if (!isset($data['measured_at'])) {
            $data['measured_at'] = Carbon::now();
        }

        $metric = OptimizationMetric::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Optimization metric created successfully',
            'data' => $metric
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $metric = OptimizationMetric::with(['warehouse', 'warehouseLayout'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $metric
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $metric = OptimizationMetric::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'exists:warehouses,id',
            'warehouse_layout_id' => 'nullable|exists:warehouse_layouts,id',
            'metric_type' => 'string|in:space_utilization,travel_distance,throughput,picking_efficiency',
            'value' => 'numeric',
            'details' => 'nullable|json',
            'measured_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $metric->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Optimization metric updated successfully',
            'data' => $metric
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $metric = OptimizationMetric::findOrFail($id);
        $metric->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Optimization metric deleted successfully'
        ]);
    }
    
    /**
     * Get metrics summary for a warehouse.
     */
    public function summary(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        
        if (!$warehouseId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Warehouse ID is required'
            ], 422);
        }
        
        $warehouse = Warehouse::findOrFail($warehouseId);
        
        // Get latest metrics for each type
        $latestMetrics = OptimizationMetric::where('warehouse_id', $warehouseId)
            ->whereNull('warehouse_layout_id')
            ->select('metric_type')
            ->selectRaw('MAX(measured_at) as latest_date')
            ->groupBy('metric_type')
            ->get()
            ->map(function ($item) use ($warehouseId) {
                $metric = OptimizationMetric::where('warehouse_id', $warehouseId)
                    ->where('metric_type', $item->metric_type)
                    ->where('measured_at', $item->latest_date)
                    ->first();
                
                return $metric;
            });
        
        // Get historical data for trends (last 30 days)
        $startDate = Carbon::now()->subDays(30);
        $historicalData = OptimizationMetric::where('warehouse_id', $warehouseId)
            ->whereNull('warehouse_layout_id')
            ->where('measured_at', '>=', $startDate)
            ->orderBy('measured_at')
            ->get()
            ->groupBy('metric_type');
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'warehouse' => $warehouse->name,
                'latest_metrics' => $latestMetrics,
                'historical_data' => $historicalData
            ]
        ]);
    }
    
    /**
     * Compare metrics between layouts.
     */
    public function compare(Request $request)
    {
        $layoutIds = $request->query('layout_ids');
        $metricType = $request->query('metric_type', 'space_utilization');
        
        if (!$layoutIds) {
            return response()->json([
                'status' => 'error',
                'message' => 'Layout IDs are required'
            ], 422);
        }
        
        $layoutIdsArray = explode(',', $layoutIds);
        
        $metrics = OptimizationMetric::whereIn('warehouse_layout_id', $layoutIdsArray)
            ->where('metric_type', $metricType)
            ->with('warehouseLayout')
            ->orderBy('measured_at', 'desc')
            ->get()
            ->groupBy('warehouse_layout_id')
            ->map(function ($items) {
                return $items->first();
            });
        
        return response()->json([
            'status' => 'success',
            'data' => $metrics
        ]);
    }
}