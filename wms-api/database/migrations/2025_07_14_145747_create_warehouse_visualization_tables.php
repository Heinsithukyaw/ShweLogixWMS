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
        // Floor plans for warehouse visualization
        Schema::create('warehouse_floor_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version')->default('1.0');
            $table->decimal('total_length', 10, 2); // warehouse dimensions
            $table->decimal('total_width', 10, 2);
            $table->decimal('total_height', 10, 2);
            $table->string('scale_unit')->default('meters'); // meters, feet
            $table->json('layout_data'); // SVG or JSON layout data
            $table->string('image_path')->nullable(); // background image
            $table->json('grid_settings')->nullable(); // grid configuration
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Equipment tracking for visualization
        Schema::create('warehouse_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type'); // forklift, conveyor, scanner, robot, etc.
            $table->string('status')->default('active'); // active, inactive, maintenance, offline
            $table->decimal('current_x', 10, 2)->nullable(); // current position
            $table->decimal('current_y', 10, 2)->nullable();
            $table->decimal('current_z', 10, 2)->nullable();
            $table->foreignId('current_zone_id')->nullable()->constrained('warehouse_zones')->onDelete('set null');
            $table->json('specifications')->nullable(); // technical specs
            $table->datetime('last_activity')->nullable();
            $table->decimal('battery_level', 5, 2)->nullable(); // for battery-powered equipment
            $table->json('sensor_data')->nullable(); // IoT sensor readings
            $table->timestamps();
            $table->softDeletes();
        });

        // Equipment movement tracking
        Schema::create('equipment_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('warehouse_equipment')->onDelete('cascade');
            $table->datetime('movement_time');
            $table->decimal('from_x', 10, 2);
            $table->decimal('from_y', 10, 2);
            $table->decimal('to_x', 10, 2);
            $table->decimal('to_y', 10, 2);
            $table->foreignId('from_zone_id')->nullable()->constrained('warehouse_zones')->onDelete('set null');
            $table->foreignId('to_zone_id')->nullable()->constrained('warehouse_zones')->onDelete('set null');
            $table->decimal('distance_traveled', 10, 2);
            $table->integer('duration_seconds');
            $table->string('movement_type')->nullable(); // task, maintenance, idle
            $table->json('path_data')->nullable(); // detailed path coordinates
            $table->timestamps();
            
            $table->index(['equipment_id', 'movement_time']);
        });

        // Real-time metrics overlay data
        Schema::create('visualization_metrics_overlay', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('warehouse_zones')->onDelete('cascade');
            $table->string('metric_type'); // utilization, activity, temperature, safety
            $table->datetime('data_time');
            $table->decimal('value', 12, 4);
            $table->string('unit_of_measure');
            $table->string('status_level'); // normal, warning, critical
            $table->json('display_properties')->nullable(); // color, size, animation
            $table->decimal('x_position', 10, 2); // overlay position
            $table->decimal('y_position', 10, 2);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            
            $table->index(['zone_id', 'metric_type', 'data_time']);
        });

        // Zone performance indicators
        Schema::create('zone_performance_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('warehouse_zones')->onDelete('cascade');
            $table->date('performance_date');
            $table->decimal('efficiency_score', 5, 2); // 0-100
            $table->decimal('utilization_score', 5, 2); // 0-100
            $table->decimal('safety_score', 5, 2); // 0-100
            $table->decimal('productivity_score', 5, 2); // 0-100
            $table->decimal('overall_score', 5, 2); // weighted average
            $table->integer('total_activities');
            $table->integer('completed_activities');
            $table->integer('error_count')->default(0);
            $table->decimal('average_completion_time', 8, 2); // in minutes
            $table->json('performance_breakdown')->nullable(); // detailed metrics
            $table->json('improvement_suggestions')->nullable();
            $table->timestamps();
            
            $table->unique(['zone_id', 'performance_date']);
        });

        // Interactive elements for floor plan
        Schema::create('floor_plan_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_plan_id')->constrained('warehouse_floor_plans')->onDelete('cascade');
            $table->string('element_type'); // zone, equipment, label, icon
            $table->string('element_id'); // reference to actual entity
            $table->decimal('x_position', 10, 2);
            $table->decimal('y_position', 10, 2);
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('rotation', 6, 2)->default(0); // degrees
            $table->json('style_properties')->nullable(); // colors, borders, etc.
            $table->json('interaction_config')->nullable(); // click, hover behaviors
            $table->boolean('is_clickable')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->integer('z_index')->default(1); // layer order
            $table->timestamps();
            
            $table->index(['floor_plan_id', 'element_type']);
        });

        // Real-time alerts for visualization
        Schema::create('visualization_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->nullable()->constrained('warehouse_zones')->onDelete('cascade');
            $table->foreignId('equipment_id')->nullable()->constrained('warehouse_equipment')->onDelete('cascade');
            $table->string('alert_type'); // safety, efficiency, maintenance, capacity
            $table->string('severity'); // low, medium, high, critical
            $table->string('title');
            $table->text('message');
            $table->datetime('alert_time');
            $table->datetime('acknowledged_at')->nullable();
            $table->string('acknowledged_by')->nullable();
            $table->datetime('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->decimal('x_position', 10, 2)->nullable(); // alert position on map
            $table->decimal('y_position', 10, 2)->nullable();
            $table->json('alert_data')->nullable(); // additional context
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['alert_time', 'is_active']);
            $table->index(['zone_id', 'alert_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visualization_alerts');
        Schema::dropIfExists('floor_plan_elements');
        Schema::dropIfExists('zone_performance_indicators');
        Schema::dropIfExists('visualization_metrics_overlay');
        Schema::dropIfExists('equipment_movements');
        Schema::dropIfExists('warehouse_equipment');
        Schema::dropIfExists('warehouse_floor_plans');
    }
};
