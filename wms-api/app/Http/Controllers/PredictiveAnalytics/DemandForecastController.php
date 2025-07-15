<?php

namespace App\Http\Controllers\PredictiveAnalytics;

use App\Http\Controllers\Controller;
use App\Models\PredictiveAnalytics\DemandForecast;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DemandForecastController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DemandForecast::with(['product', 'customer', 'createdBy']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('forecasting_method')) {
            $query->where('forecasting_method', $request->forecasting_method);
        }

        if ($request->has('date_from')) {
            $query->where('forecast_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('forecast_date', '<=', $request->date_to);
        }

        $forecasts = $query->orderBy('forecast_date', 'desc')
                          ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $forecasts
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'nullable|exists:business_parties,id',
            'forecast_period' => 'required|in:daily,weekly,monthly',
            'forecast_date' => 'required|date',
            'forecast_horizon_days' => 'required|integer|min:1|max:365',
            'forecasting_method' => 'required|in:arima,exponential_smoothing,linear_regression,seasonal_naive,machine_learning,moving_average'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $forecast = DemandForecast::create([
                'product_id' => $request->product_id,
                'customer_id' => $request->customer_id,
                'forecast_period' => $request->forecast_period,
                'forecast_date' => $request->forecast_date,
                'forecast_horizon_days' => $request->forecast_horizon_days,
                'forecasting_method' => $request->forecasting_method,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demand forecast created successfully',
                'data' => $forecast
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create forecast',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $forecast = DemandForecast::with(['product', 'customer', 'createdBy'])
                                    ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $forecast
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Forecast not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'forecast_period' => 'in:daily,weekly,monthly',
            'forecast_date' => 'date',
            'forecast_horizon_days' => 'integer|min:1|max:365',
            'forecasting_method' => 'in:arima,exponential_smoothing,linear_regression,seasonal_naive,machine_learning,moving_average'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $forecast = DemandForecast::findOrFail($id);
            $forecast->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Forecast updated successfully',
                'data' => $forecast
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update forecast',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $forecast = DemandForecast::findOrFail($id);
            $forecast->delete();

            return response()->json([
                'success' => true,
                'message' => 'Forecast deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete forecast',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateForecast(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'nullable|exists:business_parties,id',
            'method' => 'required|in:arima,exponential_smoothing,linear_regression,seasonal_naive,machine_learning,moving_average',
            'horizon_days' => 'required|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $forecast = DemandForecast::generateForecast(
                $request->product_id,
                $request->customer_id,
                $request->method,
                $request->horizon_days
            );

            return response()->json([
                'success' => true,
                'message' => 'Forecast generated successfully',
                'data' => $forecast
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate forecast',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableModels(): JsonResponse
    {
        $models = [
            'arima' => [
                'name' => 'ARIMA',
                'description' => 'AutoRegressive Integrated Moving Average',
                'best_for' => 'Time series with trends and seasonality',
                'accuracy_range' => '80-95%'
            ],
            'exponential_smoothing' => [
                'name' => 'Exponential Smoothing',
                'description' => 'Weighted average of past observations',
                'best_for' => 'Stable demand patterns',
                'accuracy_range' => '75-85%'
            ],
            'linear_regression' => [
                'name' => 'Linear Regression',
                'description' => 'Linear relationship modeling',
                'best_for' => 'Clear linear trends',
                'accuracy_range' => '70-80%'
            ],
            'seasonal_naive' => [
                'name' => 'Seasonal Naive',
                'description' => 'Previous season as forecast',
                'best_for' => 'Strong seasonal patterns',
                'accuracy_range' => '65-75%'
            ],
            'machine_learning' => [
                'name' => 'Machine Learning',
                'description' => 'Neural network based forecasting',
                'best_for' => 'Complex patterns and multiple variables',
                'accuracy_range' => '85-95%'
            ],
            'moving_average' => [
                'name' => 'Moving Average',
                'description' => 'Average of recent observations',
                'best_for' => 'Stable demand with minimal trends',
                'accuracy_range' => '60-70%'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $models
        ]);
    }

    public function getAccuracyReport(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(90));
            $dateTo = $request->get('date_to', now());

            $accuracyReport = DemandForecast::select([
                    'forecasting_method',
                    \DB::raw('COUNT(*) as total_forecasts'),
                    \DB::raw('AVG(model_accuracy) as avg_accuracy'),
                    \DB::raw('AVG(forecast_error) as avg_error'),
                    \DB::raw('MIN(model_accuracy) as min_accuracy'),
                    \DB::raw('MAX(model_accuracy) as max_accuracy')
                ])
                ->whereNotNull('actual_demand')
                ->whereBetween('forecast_date', [$dateFrom, $dateTo])
                ->groupBy('forecasting_method')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $accuracyReport
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get accuracy report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateActualDemand(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'actual_demand' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $forecast = DemandForecast::findOrFail($id);
            $forecast->updateActualDemand($request->actual_demand);

            return response()->json([
                'success' => true,
                'message' => 'Actual demand updated successfully',
                'data' => [
                    'forecast_error' => $forecast->forecast_error,
                    'model_accuracy' => $forecast->model_accuracy
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update actual demand',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}