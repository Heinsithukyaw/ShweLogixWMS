<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Packing Stations
        Schema::create('packing_stations', function (Blueprint $table) {
            $table->id();
            $table->string('station_code')->unique();
            $table->string('station_name');
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('zone_id')->nullable()->constrained();
            $table->enum('station_type', ['standard', 'express', 'fragile', 'oversized', 'multi_order']);
            $table->enum('station_status', ['active', 'inactive', 'maintenance']);
            $table->json('capabilities')->nullable(); // weight_check, dimension_check, etc.
            $table->decimal('max_weight_kg', 8, 2)->nullable();
            $table->json('equipment_list')->nullable(); // scales, printers, scanners
            $table->foreignId('assigned_to')->nullable()->constrained('employees');
            $table->boolean('is_automated')->default(false);
            $table->timestamps();
            
            $table->index(['warehouse_id', 'station_status']);
        });

        // Carton Types
        Schema::create('carton_types', function (Blueprint $table) {
            $table->id();
            $table->string('carton_code')->unique();
            $table->string('carton_name');
            $table->decimal('length_cm', 8, 2);
            $table->decimal('width_cm', 8, 2);
            $table->decimal('height_cm', 8, 2);
            $table->decimal('max_weight_kg', 8, 2);
            $table->decimal('tare_weight_kg', 8, 3);
            $table->decimal('volume_cm3', 12, 2);
            $table->enum('carton_material', ['cardboard', 'plastic', 'wood', 'metal']);
            $table->decimal('cost_per_unit', 8, 4);
            $table->boolean('is_active')->default(true);
            $table->json('usage_rules')->nullable(); // fragile, hazmat, etc.
            $table->string('supplier')->nullable();
            $table->timestamps();
            
            $table->index(['is_active', 'carton_material']);
        });

        // Packing Instructions
        Schema::create('packing_instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained();
            $table->foreignId('category_id')->nullable()->constrained();
            $table->string('instruction_type'); // product, category, general
            $table->text('packing_instructions');
            $table->json('special_requirements')->nullable(); // fragile, orientation, etc.
            $table->json('prohibited_materials')->nullable();
            $table->json('required_materials')->nullable();
            $table->boolean('requires_bubble_wrap')->default(false);
            $table->boolean('requires_padding')->default(false);
            $table->boolean('fragile_handling')->default(false);
            $table->integer('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['product_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
        });

        // Packing Materials
        Schema::create('packing_materials', function (Blueprint $table) {
            $table->id();
            $table->string('material_code')->unique();
            $table->string('material_name');
            $table->enum('material_type', ['bubble_wrap', 'padding', 'tape', 'label', 'desiccant', 'fragile_sticker']);
            $table->string('unit_of_measure');
            $table->decimal('cost_per_unit', 8, 4);
            $table->integer('current_stock');
            $table->integer('min_stock_level');
            $table->boolean('is_active')->default(true);
            $table->string('supplier')->nullable();
            $table->timestamps();
            
            $table->index(['material_type', 'is_active']);
        });

        // Pack Orders
        Schema::create('pack_orders', function (Blueprint $table) {
            $table->id();
            $table->string('pack_order_number')->unique();
            $table->foreignId('sales_order_id')->constrained();
            $table->foreignId('packing_station_id')->constrained();
            $table->foreignId('assigned_to')->nullable()->constrained('employees');
            $table->enum('pack_status', ['pending', 'assigned', 'in_progress', 'packed', 'verified', 'cancelled']);
            $table->enum('pack_priority', ['low', 'normal', 'high', 'urgent']);
            $table->integer('total_items');
            $table->integer('packed_items')->default(0);
            $table->decimal('estimated_time', 5, 2)->nullable();
            $table->decimal('actual_time', 5, 2)->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('packing_notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['pack_status', 'pack_priority']);
            $table->index(['packing_station_id', 'assigned_to']);
        });

        // Packed Cartons
        Schema::create('packed_cartons', function (Blueprint $table) {
            $table->id();
            $table->string('carton_number')->unique();
            $table->foreignId('pack_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('sales_order_id')->constrained();
            $table->foreignId('carton_type_id')->constrained();
            $table->foreignId('packing_station_id')->constrained();
            $table->foreignId('packed_by')->constrained('employees');
            $table->integer('carton_sequence'); // For multi-carton orders
            $table->decimal('gross_weight_kg', 8, 3);
            $table->decimal('net_weight_kg', 8, 3);
            $table->decimal('actual_length_cm', 8, 2)->nullable();
            $table->decimal('actual_width_cm', 8, 2)->nullable();
            $table->decimal('actual_height_cm', 8, 2)->nullable();
            $table->json('packed_items'); // Items in this carton
            $table->json('materials_used')->nullable(); // Packing materials used
            $table->enum('carton_status', ['packed', 'verified', 'shipped', 'damaged']);
            $table->timestamp('packed_at');
            $table->foreignId('verified_by')->nullable()->constrained('employees');
            $table->timestamp('verified_at')->nullable();
            $table->text('packing_notes')->nullable();
            $table->timestamps();
            
            $table->index(['pack_order_id', 'carton_sequence']);
            $table->index(['carton_status', 'packed_at']);
        });

        // Packing Validations
        Schema::create('packing_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packed_carton_id')->constrained()->onDelete('cascade');
            $table->enum('validation_type', ['weight', 'dimension', 'content', 'quality']);
            $table->enum('validation_status', ['passed', 'failed', 'warning']);
            $table->decimal('expected_value', 10, 3)->nullable();
            $table->decimal('actual_value', 10, 3)->nullable();
            $table->decimal('tolerance_percentage', 5, 2)->default(5.00);
            $table->text('validation_notes')->nullable();
            $table->json('validation_data')->nullable(); // Additional validation info
            $table->foreignId('validated_by')->constrained('employees');
            $table->timestamp('validated_at');
            $table->timestamps();
            
            $table->index(['validation_type', 'validation_status']);
        });

        // Packing Quality Checks
        Schema::create('packing_quality_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packed_carton_id')->constrained()->onDelete('cascade');
            $table->foreignId('quality_checker_id')->constrained('employees');
            $table->json('quality_criteria'); // Checklist items
            $table->json('check_results'); // Results for each criteria
            $table->enum('overall_result', ['passed', 'failed', 'conditional']);
            $table->decimal('quality_score', 5, 2)->nullable(); // 0-100
            $table->text('defects_found')->nullable();
            $table->text('corrective_actions')->nullable();
            $table->boolean('requires_repack')->default(false);
            $table->timestamp('checked_at');
            $table->timestamps();
            
            $table->index(['overall_result', 'checked_at']);
        });

        // Packing Performance
        Schema::create('packing_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('packing_station_id')->constrained();
            $table->date('performance_date');
            $table->integer('orders_packed');
            $table->integer('cartons_packed');
            $table->integer('items_packed');
            $table->decimal('total_time_hours', 5, 2);
            $table->decimal('orders_per_hour', 8, 2);
            $table->decimal('items_per_hour', 8, 2);
            $table->decimal('accuracy_rate', 5, 2); // percentage
            $table->integer('quality_failures');
            $table->decimal('rework_time_hours', 5, 2)->default(0);
            $table->json('performance_metrics')->nullable();
            $table->timestamps();
            
            $table->index(['employee_id', 'performance_date']);
            $table->index(['packing_station_id', 'performance_date']);
        });

        // Multi-Carton Shipments
        Schema::create('multi_carton_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained();
            $table->string('master_tracking_number')->nullable();
            $table->integer('total_cartons');
            $table->json('carton_ids'); // Array of packed_carton IDs
            $table->decimal('total_weight_kg', 10, 3);
            $table->decimal('total_volume_cm3', 15, 2);
            $table->enum('shipment_status', ['pending', 'ready', 'shipped', 'delivered']);
            $table->json('shipping_labels')->nullable(); // Label data for each carton
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['sales_order_id', 'shipment_status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('multi_carton_shipments');
        Schema::dropIfExists('packing_performance');
        Schema::dropIfExists('packing_quality_checks');
        Schema::dropIfExists('packing_validations');
        Schema::dropIfExists('packed_cartons');
        Schema::dropIfExists('pack_orders');
        Schema::dropIfExists('packing_materials');
        Schema::dropIfExists('packing_instructions');
        Schema::dropIfExists('carton_types');
        Schema::dropIfExists('packing_stations');
    }
};