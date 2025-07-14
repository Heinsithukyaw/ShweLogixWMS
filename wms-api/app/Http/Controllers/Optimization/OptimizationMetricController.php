<?php

namespace App\Http\Controllers\Optimization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OptimizationMetric;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OptimizationMetricController extends Controller
{
    /**
     * Display a listing of optimization metrics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = OptimizationMetric::query();
            
            // Apply filters
            if ($request->has('metric_type')) {
                $query->where('metric_type', $request->metric_type);
            }
            
            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }
            
            if ($request->has('date_from')) {
                $query->whereDate('recorded_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->whereDate('recorded_at', '<=', $request->date_to);
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'recorded_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $metrics = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $metrics,
                'message' => 'Optimization metrics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving optimization metrics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve optimization metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created optimization metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'metric_type' => 'required|string|max:50',
                'metric_name' => 'required|string|max:100',
                'metric_value' => 'required|numeric',
                'unit_of_measure' => 'required|string|max:20',
                'warehouse_id' => 'required|exists:warehouses,id',
                'recorded_at' => 'required|date',
                'recorded_by' => 'required|exists:users,id',
                'metadata' => 'nullable|json',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $metric = OptimizationMetric::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => $metric,
                'message' => 'Optimization metric created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating optimization metric: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create optimization metric',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified optimization metric.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $metric = OptimizationMetric::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $metric,
                'message' => 'Optimization metric retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving optimization metric: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Optimization metric not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified optimization metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $metric = OptimizationMetric::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'metric_type' => 'string|max:50',
                'metric_name' => 'string|max:100',
                'metric_value' => 'numeric',
                'unit_of_measure' => 'string|max:20',
                'warehouse_id' => 'exists:warehouses,id',
                'recorded_at' => 'date',
                'recorded_by' => 'exists:users,id',
                'metadata' => 'nullable|json',
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
                'data' => $metric,
                'message' => 'Optimization metric updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating optimization metric: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update optimization metric',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified optimization metric.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $metric = OptimizationMetric::findOrFail($id);
            
            $metric->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Optimization metric deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting optimization metric: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete optimization metric',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a summary of optimization metrics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getSummary(Request $request)
    {
        try {
            $warehouseId = $request->input('warehouse_id');
            $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->format('Y-m-d'));
            
            $query = OptimizationMetric::query();
            
            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }
            
            $query->whereDate('recorded_at', '>=', $dateFrom)
                  ->whereDate('recorded_at', '<=', $dateTo);
            
            $metrics = $query->get();
            
            $summary = [
                'total_metrics' => $metrics->count(),
                'date_range' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ],
                'metrics_by_type' => $metrics->groupBy('metric_type')
                    ->map(function ($items, $type) {
                        return [
                            'count' => $items->count(),
                            'average_value' => $items->avg('metric_value'),
                            'min_value' => $items->min('metric_value'),
                            'max_value' => $items->max('metric_value'),
                            'latest_value' => $items->sortByDesc('recorded_at')->first()->metric_value ?? null,
                            'unit' => $items->first()->unit_of_measure ?? null
                        ];
                    })
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $summary,
                'message' => 'Optimization metrics summary retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving optimization metrics summary: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve optimization metrics summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compare metrics between two time periods.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function compareMetrics(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'warehouse_id' => 'required|exists:warehouses,id',
                'metric_type' => 'required|string',
                'period_1_from' => 'required|date',
                'period_1_to' => 'required|date|after_or_equal:period_1_from',
                'period_2_from' => 'required|date',
                'period_2_to' => 'required|date|after_or_equal:period_2_from',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $warehouseId = $request->warehouse_id;
            $metricType = $request->metric_type;
            
            // Get metrics for period 1
            $period1Metrics = OptimizationMetric::where('warehouse_id', $warehouseId)
                ->where('metric_type', $metricType)
                ->whereDate('recorded_at', '>=', $request->period_1_from)
                ->whereDate('recorded_at', '<=', $request->period_1_to)
                ->get();
            
            // Get metrics for period 2
            $period2Metrics = OptimizationMetric::where('warehouse_id', $warehouseId)
                ->where('metric_type', $metricType)
                ->whereDate('recorded_at', '>=', $request->period_2_from)
                ->whereDate('recorded_at', '<=', $request->period_2_to)
                ->get();
            
            // Calculate statistics for both periods
            $period1Stats = [
                'count' => $period1Metrics->count(),
                'average' => $period1Metrics->avg('metric_value'),
                'min' => $period1Metrics->min('metric_value'),
                'max' => $period1Metrics->max('metric_value'),
                'unit' => $period1Metrics->first()->unit_of_measure ?? null
            ];
            
            $period2Stats = [
                'count' => $period2Metrics->count(),
                'average' => $period2Metrics->avg('metric_value'),
                'min' => $period2Metrics->min('metric_value'),
                'max' => $period2Metrics->max('metric_value'),
                'unit' => $period2Metrics->first()->unit_of_measure ?? null
            ];
            
            // Calculate differences
            $differences = [
                'average_change' => $period2Stats['average'] - $period1Stats['average'],
                'average_change_percent' => $period1Stats['average'] > 0 
                    ? (($period2Stats['average'] - $period1Stats['average']) / $period1Stats['average']) * 100 
                    : null,
                'min_change' => $period2Stats['min'] - $period1Stats['min'],
                'max_change' => $period2Stats['max'] - $period1Stats['max'],
            ];
            
            $comparison = [
                'metric_type' => $metricType,
                'warehouse_id' => $warehouseId,
                'period_1' => [
                    'from' => $request->period_1_from,
                    'to' => $request->period_1_to,
                    'stats' => $period1Stats
                ],
                'period_2' => [
                    'from' => $request->period_2_from,
                    'to' => $request->period_2_to,
                    'stats' => $period2Stats
                ],
                'differences' => $differences
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $comparison,
                'message' => 'Metrics comparison completed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error comparing optimization metrics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to compare optimization metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}