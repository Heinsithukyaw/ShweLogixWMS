<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Demand Forecasts
        Schema::create('demand_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('customer_id')->nullable()->constrained('business_parties');
            $table->enum('forecast_period', ['daily', 'weekly', 'monthly']);
            $table->date('forecast_date');
            $table->integer('forecast_horizon_days');
            $table->enum('forecasting_method', ['arima', 'exponential_smoothing', 'linear_regression', 'seasonal_naive', 'machine_learning', 'moving_average']);
            $table->integer('historical_data_points');
            $table->decimal('predicted_demand', 12, 2);
            $table->decimal('confidence_level', 5, 2);
            $table->json('seasonal_factors')->nullable();
            $table->json('trend_factors')->nullable();
            $table->json('external_factors')->nullable();
            $table->decimal('model_accuracy', 5, 2)->nullable();
            $table->decimal('actual_demand', 12, 2)->nullable();
            $table->decimal('forecast_error', 5, 2)->nullable();
            $table->json('model_parameters')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['product_id', 'forecast_date']);
            $table->index(['forecasting_method', 'model_accuracy']);
            $table->index('forecast_date');
        });

        // Cost Optimization Models
        Schema::create('cost_optimization_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_name');
            $table->text('description')->nullable();
            $table->enum('optimization_type', ['inventory', 'labor', 'transportation', 'storage', 'overall']);
            $table->json('model_parameters');
            $table->json('constraints');
            $table->json('objective_function');
            $table->enum('status', ['active', 'inactive', 'testing']);
            $table->decimal('current_cost', 15, 2)->nullable();
            $table->decimal('optimized_cost', 15, 2)->nullable();
            $table->decimal('potential_savings', 15, 2)->nullable();
            $table->json('optimization_results')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['optimization_type', 'status']);
        });

        // Layout Optimization Results
        Schema::create('layout_optimization_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('optimization_algorithm');
            $table->json('current_layout_metrics');
            $table->json('optimized_layout_data');
            $table->json('optimization_metrics');
            $table->decimal('efficiency_improvement', 5, 2);
            $table->decimal('cost_reduction', 15, 2);
            $table->json('recommendations');
            $table->enum('implementation_status', ['pending', 'approved', 'implemented', 'rejected']);
            $table->text('implementation_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['warehouse_id', 'implementation_status']);
        });

        // Performance Predictions
        Schema::create('performance_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('prediction_type');
            $table->string('entity_type');
            $table->string('entity_id');
            $table->date('prediction_date');
            $table->integer('prediction_horizon_days');
            $table->json('input_parameters');
            $table->json('predicted_metrics');
            $table->decimal('confidence_score', 5, 2);
            $table->json('contributing_factors');
            $table->json('actual_metrics')->nullable();
            $table->decimal('prediction_accuracy', 5, 2)->nullable();
            $table->enum('model_type', ['statistical', 'machine_learning', 'hybrid']);
            $table->json('model_metadata');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'prediction_date']);
            $table->index(['prediction_type', 'model_type']);
        });

        // AI Model Training History
        Schema::create('ai_model_training_history', function (Blueprint $table) {
            $table->id();
            $table->string('model_name');
            $table->string('model_type');
            $table->string('training_dataset');
            $table->json('training_parameters');
            $table->json('model_architecture')->nullable();
            $table->decimal('training_accuracy', 5, 2)->nullable();
            $table->decimal('validation_accuracy', 5, 2)->nullable();
            $table->decimal('test_accuracy', 5, 2)->nullable();
            $table->integer('training_epochs')->nullable();
            $table->integer('training_time_minutes')->nullable();
            $table->enum('status', ['training', 'completed', 'failed', 'deployed']);
            $table->text('training_notes')->nullable();
            $table->string('model_file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['model_name', 'status']);
            $table->index('training_accuracy');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_model_training_history');
        Schema::dropIfExists('performance_predictions');
        Schema::dropIfExists('layout_optimization_results');
        Schema::dropIfExists('cost_optimization_models');
        Schema::dropIfExists('demand_forecasts');
    }
};