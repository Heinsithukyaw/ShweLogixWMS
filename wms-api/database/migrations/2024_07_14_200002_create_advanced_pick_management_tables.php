<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Pick Lists
        Schema::create('pick_lists', function (Blueprint $table) {
            $table->id();
            $table->string('pick_list_number')->unique();
            $table->foreignId('pick_wave_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('employees');
            $table->enum('pick_type', ['single', 'batch', 'zone', 'cluster', 'wave']);
            $table->enum('pick_method', ['discrete', 'batch', 'zone', 'cluster']);
            $table->enum('pick_status', ['pending', 'assigned', 'in_progress', 'completed', 'cancelled']);
            $table->integer('total_picks');
            $table->integer('completed_picks')->default(0);
            $table->decimal('estimated_time', 5, 2)->nullable(); // in hours
            $table->decimal('actual_time', 5, 2)->nullable();
            $table->json('pick_sequence')->nullable(); // Optimized pick sequence
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['pick_status', 'assigned_to']);
            $table->index(['pick_wave_id', 'pick_type']);
        });

        // Pick List Items
        Schema::create('pick_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pick_list_id')->constrained()->onDelete('cascade');
            $table->foreignId('pick_task_id')->constrained()->onDelete('cascade');
            $table->foreignId('sales_order_id')->constrained();
            $table->foreignId('sales_order_item_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('location_id')->constrained();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('quantity_to_pick', 10, 3);
            $table->decimal('quantity_picked', 10, 3)->default(0);
            $table->integer('pick_sequence');
            $table->enum('pick_status', ['pending', 'in_progress', 'picked', 'short_picked', 'cancelled']);
            $table->timestamp('picked_at')->nullable();
            $table->foreignId('picked_by')->nullable()->constrained('employees');
            $table->text('pick_notes')->nullable();
            $table->timestamps();
            
            $table->index(['pick_list_id', 'pick_sequence']);
            $table->index(['location_id', 'pick_status']);
        });

        // Pick Paths
        Schema::create('pick_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('path_name');
            $table->json('location_sequence'); // Optimized location sequence
            $table->decimal('total_distance', 8, 2); // in meters
            $table->decimal('estimated_time', 5, 2); // in minutes
            $table->enum('path_type', ['serpentine', 'return', 'midpoint', 'largest_gap', 'custom']);
            $table->json('optimization_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['warehouse_id', 'is_active']);
        });

        // Batch Picks
        Schema::create('batch_picks', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->foreignId('pick_wave_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('employees');
            $table->json('sales_order_ids'); // Orders in this batch
            $table->integer('total_orders');
            $table->integer('total_items');
            $table->enum('batch_status', ['pending', 'assigned', 'in_progress', 'completed', 'cancelled']);
            $table->enum('batch_strategy', ['product_based', 'location_based', 'order_based', 'mixed']);
            $table->decimal('estimated_time', 5, 2)->nullable();
            $table->decimal('actual_time', 5, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['batch_status', 'assigned_to']);
        });

        // Zone Picks
        Schema::create('zone_picks', function (Blueprint $table) {
            $table->id();
            $table->string('zone_pick_number')->unique();
            $table->foreignId('pick_wave_id')->constrained()->onDelete('cascade');
            $table->foreignId('zone_id')->constrained();
            $table->foreignId('assigned_to')->nullable()->constrained('employees');
            $table->json('pick_list_ids'); // Pick lists for this zone
            $table->enum('zone_status', ['pending', 'assigned', 'in_progress', 'completed', 'cancelled']);
            $table->integer('total_picks');
            $table->integer('completed_picks')->default(0);
            $table->decimal('estimated_time', 5, 2)->nullable();
            $table->decimal('actual_time', 5, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['zone_id', 'zone_status']);
        });

        // Cluster Picks
        Schema::create('cluster_picks', function (Blueprint $table) {
            $table->id();
            $table->string('cluster_number')->unique();
            $table->foreignId('pick_wave_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('employees');
            $table->json('cart_configuration'); // Cart setup and order assignments
            $table->integer('cart_capacity');
            $table->integer('orders_assigned');
            $table->enum('cluster_status', ['pending', 'assigned', 'in_progress', 'completed', 'cancelled']);
            $table->decimal('estimated_time', 5, 2)->nullable();
            $table->decimal('actual_time', 5, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['cluster_status', 'assigned_to']);
        });

        // Pick Confirmations
        Schema::create('pick_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pick_task_id')->constrained()->onDelete('cascade');
            $table->foreignId('pick_list_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained();
            $table->decimal('confirmed_quantity', 10, 3);
            $table->string('confirmation_method'); // barcode, rfid, manual
            $table->string('barcode_scanned')->nullable();
            $table->json('confirmation_data')->nullable(); // Additional validation data
            $table->timestamp('confirmed_at');
            $table->boolean('requires_verification')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('employees');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index(['pick_task_id', 'confirmed_at']);
        });

        // Pick Exceptions
        Schema::create('pick_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pick_task_id')->constrained()->onDelete('cascade');
            $table->foreignId('pick_list_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('exception_type', ['short_pick', 'damage', 'location_empty', 'product_mismatch', 'system_error']);
            $table->text('exception_description');
            $table->decimal('expected_quantity', 10, 3)->nullable();
            $table->decimal('actual_quantity', 10, 3)->nullable();
            $table->enum('exception_status', ['open', 'investigating', 'resolved', 'escalated']);
            $table->json('resolution_actions')->nullable();
            $table->foreignId('reported_by')->constrained('employees');
            $table->foreignId('resolved_by')->nullable()->constrained('employees');
            $table->timestamp('reported_at');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            
            $table->index(['exception_type', 'exception_status']);
        });

        // Pick Performance
        Schema::create('pick_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('pick_list_id')->nullable()->constrained();
            $table->date('performance_date');
            $table->integer('total_picks');
            $table->integer('successful_picks');
            $table->integer('exception_picks');
            $table->decimal('pick_accuracy', 5, 2); // percentage
            $table->decimal('picks_per_hour', 8, 2);
            $table->decimal('total_time_hours', 5, 2);
            $table->decimal('travel_time_hours', 5, 2)->nullable();
            $table->decimal('pick_time_hours', 5, 2)->nullable();
            $table->json('performance_metrics')->nullable();
            $table->timestamps();
            
            $table->index(['employee_id', 'performance_date']);
            $table->index('performance_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pick_performance');
        Schema::dropIfExists('pick_exceptions');
        Schema::dropIfExists('pick_confirmations');
        Schema::dropIfExists('cluster_picks');
        Schema::dropIfExists('zone_picks');
        Schema::dropIfExists('batch_picks');
        Schema::dropIfExists('pick_paths');
        Schema::dropIfExists('pick_list_items');
        Schema::dropIfExists('pick_lists');
    }
};