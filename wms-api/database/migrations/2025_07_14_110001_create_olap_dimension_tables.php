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
        // Time dimension for OLAP
        Schema::create('olap_dim_time', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('day');
            $table->integer('month');
            $table->integer('quarter');
            $table->integer('year');
            $table->string('day_name');
            $table->string('month_name');
            $table->boolean('is_weekend');
            $table->boolean('is_holiday')->default(false);
            $table->string('fiscal_period')->nullable();
            $table->timestamps();
            
            // Unique index on date
            $table->unique('date');
        });
        
        // Product dimension for OLAP
        Schema::create('olap_dim_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->string('sku');
            $table->string('name');
            $table->string('category');
            $table->string('subcategory')->nullable();
            $table->string('brand')->nullable();
            $table->string('uom_code');
            $table->decimal('unit_cost', 15, 5)->nullable();
            $table->decimal('unit_price', 15, 5)->nullable();
            $table->string('product_group')->nullable();
            $table->string('product_type');
            $table->boolean('is_active');
            $table->timestamps();
            
            // Unique index on product_id
            $table->unique('product_id');
        });
        
        // Customer dimension for OLAP
        Schema::create('olap_dim_customer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id');
            $table->string('customer_code');
            $table->string('customer_name');
            $table->string('customer_type');
            $table->string('customer_group')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('industry')->nullable();
            $table->date('since_date')->nullable();
            $table->boolean('is_active');
            $table->timestamps();
            
            // Unique index on customer_id
            $table->unique('customer_id');
        });
        
        // Warehouse dimension for OLAP
        Schema::create('olap_dim_warehouse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('warehouse_code');
            $table->string('warehouse_name');
            $table->string('warehouse_type');
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->decimal('total_area', 15, 2)->nullable();
            $table->integer('zone_count')->nullable();
            $table->integer('location_count')->nullable();
            $table->boolean('is_active');
            $table->timestamps();
            
            // Unique index on warehouse_id
            $table->unique('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olap_dim_warehouse');
        Schema::dropIfExists('olap_dim_customer');
        Schema::dropIfExists('olap_dim_product');
        Schema::dropIfExists('olap_dim_time');
    }
};