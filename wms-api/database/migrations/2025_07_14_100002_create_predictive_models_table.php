<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('predictive_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model_type'); // inventory_forecast, demand_prediction, resource_optimization
            $table->text('description')->nullable();
            $table->json('model_parameters');
            $table->json('training_metrics')->nullable();
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_trained_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictive_models');
    }
};