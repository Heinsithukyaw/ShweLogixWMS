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
        // Warehouse zones for space utilization
        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('storage'); // storage, picking, receiving, shipping, staging
            $table->decimal('length', 10, 2); // in meters
            $table->decimal('width', 10, 2); // in meters
            $table->decimal('height', 10, 2); // in meters
            $table->decimal('total_area', 12, 2); // calculated area
            $table->decimal('total_volume', 15, 2); // calculated volume
            $table->decimal('usable_area', 12, 2); // area available for storage
            $table->decimal('usable_volume', 15, 2); // volume available for storage
            $table->integer('max_capacity'); // maximum items/pallets
            $table->json('coordinates')->nullable(); // x, y coordinates for visualization
            $table->json('boundaries')->nullable(); // polygon boundaries
            $table->string('status')->default('active'); // active, inactive, maintenance
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Aisles within zones
        Schema::create('warehouse_aisles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('warehouse_zones')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('length', 10, 2);
            $table->decimal('width', 10, 2);
            $table->decimal('height', 10, 2);
            $table->integer('location_count'); // number of storage locations
            $table->integer('occupied_locations')->default(0);
            $table->decimal('utilization_percentage', 5, 2)->default(0);
            $table->json('coordinates')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        // Space utilization snapshots
        Schema::create('space_utilization_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('warehouse_zones')->onDelete('cascade');
            $table->foreignId('aisle_id')->nullable()->constrained('warehouse_aisles')->onDelete('cascade');
            $table->datetime('snapshot_time');
            $table->decimal('occupied_area', 12, 2);
            $table->decimal('occupied_volume', 15, 2);
            $table->integer('occupied_locations');
            $table->integer('total_locations');
            $table->decimal('utilization_percentage', 5, 2);
            $table->decimal('density_per_sqm', 8, 2); // items per square meter
            $table->decimal('density_per_cbm', 8, 2); // items per cubic meter
            $table->integer('item_count');
            $table->decimal('weight_total', 12, 2)->nullable();
            $table->json('utilization_by_category')->nullable(); // breakdown by product category
            $table->timestamps();
            
            $table->index(['zone_id', 'snapshot_time']);
            $table->index(['aisle_id', 'snapshot_time']);
        });

        // Capacity tracking
        Schema::create('capacity_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('warehouse_zones')->onDelete('cascade');
            $table->date('tracking_date');
            $table->integer('max_capacity');
            $table->integer('current_occupancy');
            $table->integer('reserved_capacity')->default(0);
            $table->integer('available_capacity');
            $table->decimal('capacity_utilization', 5, 2);
            $table->decimal('peak_utilization', 5, 2)->default(0);
            $table->time('peak_time')->nullable();
            $table->json('hourly_utilization')->nullable(); // 24-hour breakdown
            $table->json('capacity_forecast')->nullable(); // next 7 days forecast
            $table->timestamps();
            
            $table->unique(['zone_id', 'tracking_date']);
        });

        // Aisle efficiency metrics
        Schema::create('aisle_efficiency_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aisle_id')->constrained('warehouse_aisles')->onDelete('cascade');
            $table->date('metric_date');
            $table->decimal('pick_density', 8, 2); // picks per meter
            $table->decimal('travel_distance', 10, 2); // average travel distance
            $table->decimal('pick_time_avg', 8, 2); // average pick time in seconds
            $table->integer('congestion_incidents')->default(0);
            $table->decimal('accessibility_score', 5, 2); // 0-100 score
            $table->decimal('efficiency_score', 5, 2); // 0-100 overall efficiency
            $table->json('peak_hours')->nullable(); // busiest hours
            $table->json('bottleneck_locations')->nullable(); // problem spots
            $table->timestamps();
            
            $table->unique(['aisle_id', 'metric_date']);
        });

        // Heat map data for visualization
        Schema::create('heat_map_data', function (Blueprint $table) {
            $table->id();
            $table->string('map_type'); // utilization, activity, efficiency, temperature
            $table->foreignId('zone_id')->nullable()->constrained('warehouse_zones')->onDelete('cascade');
            $table->foreignId('aisle_id')->nullable()->constrained('warehouse_aisles')->onDelete('cascade');
            $table->datetime('data_time');
            $table->decimal('x_coordinate', 10, 2);
            $table->decimal('y_coordinate', 10, 2);
            $table->decimal('intensity', 8, 4); // heat intensity value
            $table->string('intensity_level'); // low, medium, high, critical
            $table->json('metadata')->nullable(); // additional context data
            $table->timestamps();
            
            $table->index(['map_type', 'data_time']);
            $table->index(['zone_id', 'map_type', 'data_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('heat_map_data');
        Schema::dropIfExists('aisle_efficiency_metrics');
        Schema::dropIfExists('capacity_tracking');
        Schema::dropIfExists('space_utilization_snapshots');
        Schema::dropIfExists('warehouse_aisles');
        Schema::dropIfExists('warehouse_zones');
    }
};
