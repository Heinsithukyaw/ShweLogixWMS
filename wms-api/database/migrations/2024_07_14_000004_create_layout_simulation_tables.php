<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Layout Simulations
        Schema::create('layout_simulations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('base_layout_id')->nullable()->constrained('layout_simulations');
            $table->json('layout_data');
            $table->json('simulation_parameters')->nullable();
            $table->enum('status', ['draft', 'running', 'completed', 'failed'])->default('draft');
            $table->json('simulation_results')->nullable();
            $table->json('kpi_predictions')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamp('last_simulated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'status']);
            $table->index('last_simulated_at');
        });

        // Layout Elements
        Schema::create('layout_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_simulation_id')->constrained()->onDelete('cascade');
            $table->string('element_type');
            $table->string('element_name');
            $table->decimal('position_x', 10, 2);
            $table->decimal('position_y', 10, 2);
            $table->decimal('width', 10, 2);
            $table->decimal('height', 10, 2);
            $table->decimal('rotation', 5, 2)->default(0);
            $table->json('properties')->nullable();
            $table->json('constraints')->nullable();
            $table->boolean('is_movable')->default(true);
            $table->boolean('is_resizable')->default(true);
            $table->integer('z_index')->default(0);
            $table->timestamps();

            $table->index(['layout_simulation_id', 'element_type']);
            $table->index(['position_x', 'position_y']);
        });

        // Simulation Scenarios
        Schema::create('simulation_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_simulation_id')->constrained()->onDelete('cascade');
            $table->string('scenario_name');
            $table->text('description')->nullable();
            $table->json('scenario_parameters');
            $table->json('results')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->boolean('is_baseline')->default(false);
            $table->timestamps();

            $table->index(['layout_simulation_id', 'is_baseline']);
        });

        // Layout Comparisons
        Schema::create('layout_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('base_layout_id')->constrained('layout_simulations');
            $table->foreignId('comparison_layout_id')->constrained('layout_simulations');
            $table->json('comparison_results');
            $table->json('kpi_differences');
            $table->text('recommendations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['base_layout_id', 'comparison_layout_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('layout_comparisons');
        Schema::dropIfExists('simulation_scenarios');
        Schema::dropIfExists('layout_elements');
        Schema::dropIfExists('layout_simulations');
    }
};