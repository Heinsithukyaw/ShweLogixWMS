<?php

namespace App\Http\Controllers\Optimization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PredictiveModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PredictiveModelController extends Controller
{
    /**
     * Display a listing of predictive models.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = PredictiveModel::query();
            
            // Apply filters
            if ($request->has('model_type')) {
                $query->where('model_type', $request->model_type);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $models = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $models,
                'message' => 'Predictive models retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving predictive models: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve predictive models',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created predictive model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'description' => 'nullable|string',
                'model_type' => 'required|string|in:demand_forecasting,labor_optimization,inventory_optimization,slotting_optimization,pick_path_optimization',
                'algorithm' => 'required|string|in:linear_regression,random_forest,neural_network,arima,prophet,xgboost,custom',
                'features' => 'required|json',
                'parameters' => 'required|json',
                'training_data_query' => 'nullable|string',
                'created_by' => 'required|exists:users,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $model = PredictiveModel::create([
                'name' => $request->name,
                'description' => $request->description,
                'model_type' => $request->model_type,
                'algorithm' => $request->algorithm,
                'features' => $request->features,
                'parameters' => $request->parameters,
                'training_data_query' => $request->training_data_query,
                'status' => 'created',
                'created_by' => $request->created_by,
                'last_trained_at' => null,
                'performance_metrics' => null,
                'model_path' => null,
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $model,
                'message' => 'Predictive model created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating predictive model: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create predictive model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified predictive model.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $model = PredictiveModel::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $model,
                'message' => 'Predictive model retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving predictive model: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Predictive model not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified predictive model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $model = PredictiveModel::findOrFail($id);
            
            // Only allow updates if the model is not currently training
            if ($model->status === 'training') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update model while it is training'
                ], 400);
            }
            
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:100',
                'description' => 'nullable|string',
                'model_type' => 'string|in:demand_forecasting,labor_optimization,inventory_optimization,slotting_optimization,pick_path_optimization',
                'algorithm' => 'string|in:linear_regression,random_forest,neural_network,arima,prophet,xgboost,custom',
                'features' => 'json',
                'parameters' => 'json',
                'training_data_query' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $model->update($request->all());
            
            // If model configuration has changed, reset the training status
            if ($request->has('algorithm') || $request->has('features') || $request->has('parameters')) {
                $model->update([
                    'status' => 'modified',
                    'last_trained_at' => null,
                    'performance_metrics' => null,
                    'model_path' => null,
                ]);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $model,
                'message' => 'Predictive model updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating predictive model: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update predictive model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified predictive model.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $model = PredictiveModel::findOrFail($id);
            
            // Only allow deletion if the model is not currently training
            if ($model->status === 'training') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete model while it is training'
                ], 400);
            }
            
            $model->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Predictive model deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting predictive model: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete predictive model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Train the predictive model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function trainModel(Request $request, $id)
    {
        try {
            $model = PredictiveModel::findOrFail($id);
            
            // Only allow training if the model is not already training
            if ($model->status === 'training') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Model is already training'
                ], 400);
            }
            
            // Update model status to training
            $model->update([
                'status' => 'training'
            ]);
            
            // In a real implementation, this would dispatch a job to train the model asynchronously
            // For this example, we'll simulate training by updating the model with mock results
            
            // Simulate training delay
            sleep(2);
            
            // Update model with training results
            $model->update([
                'status' => 'trained',
                'last_trained_at' => now(),
                'performance_metrics' => json_encode([
                    'accuracy' => rand(80, 95) / 100,
                    'precision' => rand(75, 95) / 100,
                    'recall' => rand(75, 95) / 100,
                    'f1_score' => rand(75, 95) / 100,
                    'training_time' => rand(10, 300),
                    'data_points' => rand(1000, 10000)
                ]),
                'model_path' => 'models/' . $model->id . '_' . time() . '.pkl'
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => $model,
                'message' => 'Predictive model trained successfully'
            ]);
        } catch (\Exception $e) {
            // If an error occurs, update the model status
            if (isset($model)) {
                $model->update([
                    'status' => 'error'
                ]);
            }
            
            Log::error('Error training predictive model: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to train predictive model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make predictions using the trained model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function predict(Request $request, $id)
    {
        try {
            $model = PredictiveModel::findOrFail($id);
            
            // Check if the model is trained
            if ($model->status !== 'trained') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Model is not trained yet'
                ], 400);
            }
            
            $validator = Validator::make($request->all(), [
                'input_data' => 'required|json',
                'prediction_horizon' => 'nullable|integer|min:1',
                'confidence_interval' => 'nullable|boolean'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $inputData = json_decode($request->input_data, true);
            $predictionHorizon = $request->input('prediction_horizon', 1);
            $confidenceInterval = $request->input('confidence_interval', false);
            
            // In a real implementation, this would load the model and make predictions
            // For this example, we'll generate mock predictions
            
            $predictions = [];
            $now = now();
            
            for ($i = 0; $i < $predictionHorizon; $i++) {
                $date = $now->copy()->addDays($i)->format('Y-m-d');
                
                $prediction = [
                    'date' => $date,
                    'value' => rand(100, 1000) / 10
                ];
                
                if ($confidenceInterval) {
                    $prediction['lower_bound'] = $prediction['value'] * 0.9;
                    $prediction['upper_bound'] = $prediction['value'] * 1.1;
                }
                
                $predictions[] = $prediction;
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'model_id' => $model->id,
                    'model_name' => $model->name,
                    'model_type' => $model->model_type,
                    'prediction_time' => now()->toIso8601String(),
                    'predictions' => $predictions
                ],
                'message' => 'Prediction completed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error making prediction: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to make prediction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get model performance metrics.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getPerformance($id)
    {
        try {
            $model = PredictiveModel::findOrFail($id);
            
            // Check if the model is trained
            if ($model->status !== 'trained') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Model is not trained yet'
                ], 400);
            }
            
            $performanceMetrics = json_decode($model->performance_metrics, true);
            
            // Add additional historical performance data
            $historicalPerformance = [];
            $now = now();
            
            for ($i = 5; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i)->format('Y-m-d');
                
                $historicalPerformance[] = [
                    'date' => $date,
                    'accuracy' => rand(80, 95) / 100,
                    'precision' => rand(75, 95) / 100,
                    'recall' => rand(75, 95) / 100,
                    'f1_score' => rand(75, 95) / 100,
                ];
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'model_id' => $model->id,
                    'model_name' => $model->name,
                    'model_type' => $model->model_type,
                    'last_trained_at' => $model->last_trained_at,
                    'current_performance' => $performanceMetrics,
                    'historical_performance' => $historicalPerformance
                ],
                'message' => 'Model performance retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving model performance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve model performance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}