<?php

namespace App\Http\Controllers\Admin\api\v1\optimization;

use App\Http\Controllers\Controller;
use App\Models\PredictiveModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PredictiveModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modelType = $request->query('model_type');
        $isActive = $request->query('is_active');
        
        $query = PredictiveModel::query();
        
        if ($modelType) {
            $query->where('model_type', $modelType);
        }
        
        if ($isActive !== null) {
            $query->where('is_active', $isActive === 'true' || $isActive === '1');
        }
        
        $models = $query->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => $models
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'model_type' => 'required|string|in:inventory_forecast,demand_prediction,resource_optimization',
            'description' => 'nullable|string',
            'model_parameters' => 'required|json',
            'training_metrics' => 'nullable|json',
            'accuracy' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'last_trained_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        if (!isset($data['last_trained_at']) && isset($data['training_metrics'])) {
            $data['last_trained_at'] = Carbon::now();
        }

        $model = PredictiveModel::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Predictive model created successfully',
            'data' => $model
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = PredictiveModel::findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $model
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $model = PredictiveModel::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'model_type' => 'string|in:inventory_forecast,demand_prediction,resource_optimization',
            'description' => 'nullable|string',
            'model_parameters' => 'json',
            'training_metrics' => 'nullable|json',
            'accuracy' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'last_trained_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $model->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Predictive model updated successfully',
            'data' => $model
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $model = PredictiveModel::findOrFail($id);
        $model->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Predictive model deleted successfully'
        ]);
    }
    
    /**
     * Train a predictive model.
     */
    public function train(string $id, Request $request)
    {
        $model = PredictiveModel::findOrFail($id);
        
        // In a real implementation, this would trigger a background job
        // to train the model using historical data
        
        // For demonstration purposes, we'll simulate a successful training
        $model->training_metrics = [
            'epochs' => 100,
            'loss' => 0.0123,
            'val_loss' => 0.0145,
            'training_time' => '00:05:23',
        ];
        $model->accuracy = 95.5;
        $model->last_trained_at = Carbon::now();
        $model->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Predictive model trained successfully',
            'data' => $model
        ]);
    }
    
    /**
     * Make predictions using a model.
     */
    public function predict(string $id, Request $request)
    {
        $model = PredictiveModel::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'input_data' => 'required|json',
            'prediction_horizon' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // In a real implementation, this would use the model to make predictions
        // based on the input data
        
        // For demonstration purposes, we'll return simulated predictions
        $inputData = json_decode($request->input('input_data'), true);
        $horizon = $request->input('prediction_horizon', 7);
        
        $predictions = [];
        $baseValue = 100;
        $trend = 0.05;
        $seasonality = 0.1;
        
        for ($i = 1; $i <= $horizon; $i++) {
            $date = Carbon::now()->addDays($i)->format('Y-m-d');
            $value = $baseValue * (1 + $trend * $i) * (1 + $seasonality * sin($i * 0.5));
            
            $predictions[] = [
                'date' => $date,
                'value' => round($value, 2),
                'confidence_lower' => round($value * 0.9, 2),
                'confidence_upper' => round($value * 1.1, 2),
            ];
        }
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'model' => $model->name,
                'model_type' => $model->model_type,
                'accuracy' => $model->accuracy,
                'predictions' => $predictions
            ]
        ]);
    }
    
    /**
     * Activate a model and deactivate others of the same type.
     */
    public function activate(string $id)
    {
        $model = PredictiveModel::findOrFail($id);
        
        // Deactivate all models of the same type
        PredictiveModel::where('model_type', $model->model_type)
            ->where('id', '!=', $id)
            ->update(['is_active' => false]);
        
        // Activate this model
        $model->is_active = true;
        $model->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Predictive model activated successfully',
            'data' => $model
        ]);
    }
}