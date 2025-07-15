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
        // Fact table for inventory movements
        Schema::create('olap_fact_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('movement_type'); // receipt, putaway, pick, transfer, adjustment
            $table->decimal('quantity', 15, 5);
            $table->string('uom_code');
            $table->timestamp('movement_date');
            $table->string('reference_type')->nullable(); // order, receipt, adjustment
            $table->string('reference_id')->nullable();
            $table->timestamps();
            
            // Indexes for OLAP performance
            $table->index(['product_id', 'movement_date']);
            $table->index(['warehouse_id', 'movement_date']);
            $table->index(['movement_type', 'movement_date']);
        });
        
        // Fact table for order processing
        Schema::create('olap_fact_order_processing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable();
            $table->string('order_number');
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('order_type');
            $table->string('order_status');
            $table->integer('line_count');
            $table->integer('item_count');
            $table->decimal('total_value', 15, 2);
            $table->string('currency_code');
            $table->timestamp('order_date');
            $table->timestamp('processing_start')->nullable();
            $table->timestamp('processing_complete')->nullable();
            $table->timestamp('shipping_date')->nullable();
            $table->timestamp('delivery_date')->nullable();
            $table->decimal('processing_time_minutes', 10, 2)->nullable();
            $table->timestamps();
            
            // Indexes for OLAP performance
            $table->index(['warehouse_id', 'order_date']);
            $table->index(['order_status', 'order_date']);
            $table->index(['customer_id', 'order_date']);
        });
        
        // Fact table for warehouse operations
        Schema::create('olap_fact_warehouse_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('operation_type'); // receiving, putaway, picking, packing, shipping
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('equipment_id')->nullable();
            $table->integer('task_count');
            $table->decimal('total_quantity', 15, 5);
            $table->decimal('operation_time_minutes', 10, 2);
            $table->date('operation_date');
            $table->time('operation_hour');
            $table->timestamps();
            
            // Indexes for OLAP performance
            $table->index(['warehouse_id', 'operation_date']);
            $table->index(['operation_type', 'operation_date']);
            $table->index(['user_id', 'operation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olap_fact_warehouse_operations');
        Schema::dropIfExists('olap_fact_order_processing');
        Schema::dropIfExists('olap_fact_inventory_movements');
    }
};