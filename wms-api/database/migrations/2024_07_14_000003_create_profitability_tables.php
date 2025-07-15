<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Profitability Analyses
        Schema::create('profitability_analyses', function (Blueprint $table) {
            $table->id();
            $table->enum('analysis_period', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom']);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('analysis_type');
            $table->string('entity_type');
            $table->string('entity_id');
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_costs', 15, 2)->default(0);
            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->decimal('gross_margin_percentage', 5, 2)->default(0);
            $table->decimal('operating_costs', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->decimal('net_margin_percentage', 5, 2)->default(0);
            $table->enum('cost_allocation_method', ['traditional', 'abc', 'direct', 'step_down', 'reciprocal']);
            $table->json('cost_breakdown')->nullable();
            $table->json('revenue_breakdown')->nullable();
            $table->json('kpi_metrics')->nullable();
            $table->text('analysis_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['analysis_period', 'period_start', 'period_end']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('analysis_type');
        });

        // Cost Allocations
        Schema::create('cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profitability_analysis_id')->constrained()->onDelete('cascade');
            $table->enum('allocation_method', ['traditional', 'abc', 'direct', 'step_down', 'reciprocal']);
            $table->string('cost_category');
            $table->string('cost_driver');
            $table->decimal('total_cost', 15, 2);
            $table->decimal('allocated_amount', 15, 2);
            $table->decimal('allocation_percentage', 5, 2);
            $table->string('allocation_basis');
            $table->json('allocation_rules')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cost_category', 'cost_driver']);
        });

        // Profitability Metrics
        Schema::create('profitability_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profitability_analysis_id')->constrained()->onDelete('cascade');
            $table->string('metric_name');
            $table->decimal('metric_value', 15, 4);
            $table->string('metric_unit');
            $table->string('calculation_method');
            $table->json('calculation_parameters')->nullable();
            $table->timestamps();

            $table->index('metric_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('profitability_metrics');
        Schema::dropIfExists('cost_allocations');
        Schema::dropIfExists('profitability_analyses');
    }
};