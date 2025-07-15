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
        // Metrics Definition System
        Schema::create('metric_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description');
            $table->string('category'); // inbound, inventory, outbound, performance
            $table->string('unit_of_measure');
            $table->string('calculation_formula')->nullable();
            $table->string('data_source')->nullable();
            $table->string('frequency'); // real-time, hourly, daily, weekly, monthly
            $table->boolean('is_kpi')->default(false);
            $table->decimal('target_value', 15, 2)->nullable();
            $table->decimal('threshold_warning', 15, 2)->nullable();
            $table->decimal('threshold_critical', 15, 2)->nullable();
            $table->boolean('higher_is_better')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Data Collection Mechanisms
        Schema::create('data_collection_points', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('collection_method'); // system, manual, sensor, calculated
            $table->string('data_type'); // numeric, text, boolean, timestamp
            $table->string('source_table')->nullable();
            $table->string('source_column')->nullable();
            $table->string('aggregation_method')->nullable(); // sum, avg, min, max, count
            $table->string('validation_rule')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Metric Data
        Schema::create('metric_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('metric_definition_id');
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->unsignedBigInteger('business_party_id')->nullable();
            $table->decimal('value', 15, 4);
            $table->timestamp('measurement_time');
            $table->string('status')->nullable(); // normal, warning, critical
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('metric_definition_id')->references('id')->on('metric_definitions');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('set null');
            $table->foreign('business_party_id')->references('id')->on('business_parties')->onDelete('set null');
            
            // Add index for faster queries
            $table->index(['metric_definition_id', 'measurement_time']);
            $table->index(['warehouse_id', 'measurement_time']);
        });
        
        // Inbound Metrics
        Schema::create('inbound_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->date('date');
            $table->integer('total_receipts');
            $table->integer('total_lines_received');
            $table->integer('total_units_received');
            $table->decimal('receiving_accuracy', 5, 2)->default(0); // percentage
            $table->decimal('dock_to_stock_time', 10, 2)->default(0); // hours
            $table->integer('putaway_tasks_completed');
            $table->decimal('putaway_accuracy', 5, 2)->default(0); // percentage
            $table->decimal('receiving_cost_per_line', 10, 2)->default(0);
            $table->decimal('receiving_cost_per_unit', 10, 2)->default(0);
            $table->integer('receipts_processed_per_hour');
            $table->integer('labor_hours');
            $table->decimal('unloading_time_per_truck', 10, 2)->default(0); // hours
            $table->integer('trucks_received');
            $table->integer('pallets_received');
            $table->integer('damaged_items_received');
            $table->integer('vendor_compliance_issues');
            $table->decimal('receiving_utilization', 5, 2)->default(0); // percentage
            $table->timestamps();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            
            // Add index for faster queries
            $table->index(['warehouse_id', 'date']);
        });
        
        // Inventory Metrics
        Schema::create('inventory_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->date('date');
            $table->integer('total_sku_count');
            $table->integer('total_inventory_units');
            $table->decimal('inventory_accuracy', 5, 2)->default(0); // percentage
            $table->decimal('inventory_turnover', 5, 2)->default(0);
            $table->integer('days_on_hand');
            $table->decimal('storage_utilization', 5, 2)->default(0); // percentage
            $table->decimal('location_accuracy', 5, 2)->default(0); // percentage
            $table->integer('stockouts_count');
            $table->integer('slow_moving_items');
            $table->integer('obsolete_inventory_units');
            $table->decimal('obsolete_inventory_value', 15, 2)->default(0);
            $table->integer('cycle_count_adjustments');
            $table->decimal('cycle_count_accuracy', 5, 2)->default(0); // percentage
            $table->integer('inventory_adjustments');
            $table->decimal('inventory_shrinkage', 15, 2)->default(0);
            $table->decimal('inventory_value', 15, 2)->default(0);
            $table->integer('damaged_inventory_units');
            $table->decimal('damaged_inventory_value', 15, 2)->default(0);
            $table->integer('expired_inventory_units');
            $table->decimal('expired_inventory_value', 15, 2)->default(0);
            $table->timestamps();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            
            // Add index for faster queries
            $table->index(['warehouse_id', 'date']);
        });
        
        // Outbound Metrics
        Schema::create('outbound_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->date('date');
            $table->integer('total_orders_shipped');
            $table->integer('total_lines_shipped');
            $table->integer('total_units_shipped');
            $table->decimal('order_accuracy', 5, 2)->default(0); // percentage
            $table->decimal('picking_accuracy', 5, 2)->default(0); // percentage
            $table->decimal('shipping_accuracy', 5, 2)->default(0); // percentage
            $table->decimal('on_time_shipping', 5, 2)->default(0); // percentage
            $table->decimal('order_cycle_time', 10, 2)->default(0); // hours
            $table->decimal('picking_time_per_line', 10, 2)->default(0); // minutes
            $table->decimal('picking_time_per_unit', 10, 2)->default(0); // minutes
            $table->decimal('packing_time_per_order', 10, 2)->default(0); // minutes
            $table->integer('orders_picked_per_hour');
            $table->integer('lines_picked_per_hour');
            $table->integer('units_picked_per_hour');
            $table->integer('orders_packed_per_hour');
            $table->decimal('picking_cost_per_line', 10, 2)->default(0);
            $table->decimal('picking_cost_per_order', 10, 2)->default(0);
            $table->decimal('shipping_cost_per_order', 10, 2)->default(0);
            $table->integer('perfect_order_count');
            $table->decimal('perfect_order_percentage', 5, 2)->default(0); // percentage
            $table->integer('backorders_count');
            $table->decimal('backorder_rate', 5, 2)->default(0); // percentage
            $table->integer('canceled_orders');
            $table->integer('returns_processed');
            $table->decimal('return_rate', 5, 2)->default(0); // percentage
            $table->integer('labor_hours');
            $table->decimal('dock_utilization', 5, 2)->default(0); // percentage
            $table->integer('trucks_loaded');
            $table->integer('pallets_shipped');
            $table->integer('peak_hourly_orders');
            $table->integer('same_day_shipments');
            $table->integer('next_day_shipments');
            $table->integer('expedited_shipments');
            $table->integer('late_shipments');
            $table->decimal('average_items_per_order', 10, 2)->default(0);
            $table->decimal('average_order_value', 15, 2)->default(0);
            $table->timestamps();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            
            // Add index for faster queries
            $table->index(['warehouse_id', 'date']);
        });
        
        // Performance Metrics
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->date('date');
            $table->decimal('labor_efficiency', 5, 2)->default(0); // percentage
            $table->decimal('labor_utilization', 5, 2)->default(0); // percentage
            $table->decimal('equipment_utilization', 5, 2)->default(0); // percentage
            $table->decimal('space_utilization', 5, 2)->default(0); // percentage
            $table->decimal('cost_per_order', 15, 2)->default(0);
            $table->decimal('cost_per_line', 15, 2)->default(0);
            $table->decimal('cost_per_unit', 15, 2)->default(0);
            $table->decimal('total_labor_hours', 10, 2)->default(0);
            $table->decimal('total_labor_cost', 15, 2)->default(0);
            $table->decimal('revenue_per_labor_hour', 15, 2)->default(0);
            $table->decimal('units_per_labor_hour', 10, 2)->default(0);
            $table->decimal('orders_per_labor_hour', 10, 2)->default(0);
            $table->decimal('lines_per_labor_hour', 10, 2)->default(0);
            $table->decimal('overtime_hours', 10, 2)->default(0);
            $table->decimal('overtime_percentage', 5, 2)->default(0); // percentage
            $table->integer('safety_incidents');
            $table->decimal('energy_consumption', 15, 2)->default(0); // kWh
            $table->decimal('energy_cost', 15, 2)->default(0);
            $table->decimal('total_operating_cost', 15, 2)->default(0);
            $table->decimal('cost_as_percentage_of_revenue', 5, 2)->default(0); // percentage
            $table->decimal('throughput_per_square_foot', 10, 2)->default(0);
            $table->decimal('revenue_per_square_foot', 15, 2)->default(0);
            $table->decimal('profit_per_square_foot', 15, 2)->default(0);
            $table->decimal('customer_satisfaction_score', 5, 2)->default(0); // 0-10 scale
            $table->integer('customer_complaints');
            $table->decimal('employee_turnover', 5, 2)->default(0); // percentage
            $table->decimal('training_hours', 10, 2)->default(0);
            $table->integer('cross_trained_employees');
            $table->decimal('equipment_downtime', 10, 2)->default(0); // hours
            $table->decimal('maintenance_cost', 15, 2)->default(0);
            $table->decimal('system_uptime', 5, 2)->default(0); // percentage
            $table->integer('system_issues');
            $table->decimal('average_dock_door_utilization', 5, 2)->default(0); // percentage
            $table->decimal('average_equipment_utilization', 5, 2)->default(0); // percentage
            $table->decimal('peak_capacity_utilization', 5, 2)->default(0); // percentage
            $table->decimal('carbon_footprint', 15, 2)->default(0); // CO2 equivalent
            $table->decimal('waste_generated', 15, 2)->default(0); // kg
            $table->decimal('recycling_rate', 5, 2)->default(0); // percentage
            $table->decimal('water_usage', 15, 2)->default(0); // liters
            $table->decimal('transportation_cost', 15, 2)->default(0);
            $table->decimal('transportation_cost_per_order', 15, 2)->default(0);
            $table->timestamps();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            
            // Add index for faster queries
            $table->index(['warehouse_id', 'date']);
        });
        
        // Visualization Components
        Schema::create('metric_visualizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // chart, gauge, table, card, etc.
            $table->text('configuration'); // JSON configuration
            $table->unsignedBigInteger('metric_definition_id')->nullable();
            $table->string('time_range')->default('day'); // day, week, month, quarter, year
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('metric_definition_id')->references('id')->on('metric_definitions')->onDelete('set null');
        });
        
        // Dashboard Components
        Schema::create('dashboards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category'); // operational, financial, executive, etc.
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Dashboard Widgets
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dashboard_id');
            $table->unsignedBigInteger('metric_visualization_id');
            $table->integer('position_x');
            $table->integer('position_y');
            $table->integer('width');
            $table->integer('height');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('dashboard_id')->references('id')->on('dashboards')->onDelete('cascade');
            $table->foreign('metric_visualization_id')->references('id')->on('metric_visualizations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboards');
        Schema::dropIfExists('metric_visualizations');
        Schema::dropIfExists('performance_metrics');
        Schema::dropIfExists('outbound_metrics');
        Schema::dropIfExists('inventory_metrics');
        Schema::dropIfExists('inbound_metrics');
        Schema::dropIfExists('metric_data');
        Schema::dropIfExists('data_collection_points');
        Schema::dropIfExists('metric_definitions');
    }
};
