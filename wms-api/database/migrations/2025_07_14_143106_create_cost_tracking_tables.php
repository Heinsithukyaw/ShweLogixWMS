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
        // Cost Categories Table
        Schema::create('cost_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('parent_id')->references('id')->on('cost_categories')->onDelete('set null');
        });
        
        // Overhead Costs Table
        Schema::create('overhead_costs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('cost_category_id');
            $table->decimal('amount', 15, 2);
            $table->string('frequency'); // monthly, quarterly, yearly
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('cost_category_id')->references('id')->on('cost_categories')->onDelete('cascade');
        });
        
        // Storage Costs Table
        Schema::create('storage_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('cost_per_unit', 15, 2);
            $table->string('unit_type'); // sqft, cbm, pallet position
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });
        
        // Handling Costs Table
        Schema::create('handling_costs', function (Blueprint $table) {
            $table->id();
            $table->string('activity_type'); // receiving, putaway, picking, packing, shipping
            $table->decimal('cost_per_unit', 15, 2);
            $table->string('unit_type'); // per item, per carton, per pallet, per order
            $table->unsignedBigInteger('warehouse_id');
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
        });
        
        // Budget vs Actual Table
        Schema::create('budget_vs_actual', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cost_category_id');
            $table->decimal('budgeted_amount', 15, 2);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->decimal('variance_amount', 15, 2)->default(0);
            $table->decimal('variance_percentage', 8, 2)->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('period_type'); // monthly, quarterly, yearly
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('cost_category_id')->references('id')->on('cost_categories');
        });
        
        // Cost Allocation Table
        Schema::create('cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cost_category_id');
            $table->unsignedBigInteger('business_party_id')->nullable(); // client/customer
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->decimal('allocated_amount', 15, 2);
            $table->string('allocation_method'); // direct, proportional, activity-based
            $table->date('allocation_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('cost_category_id')->references('id')->on('cost_categories');
            $table->foreign('business_party_id')->references('id')->on('business_parties')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_allocations');
        Schema::dropIfExists('budget_vs_actual');
        Schema::dropIfExists('handling_costs');
        Schema::dropIfExists('storage_costs');
        Schema::dropIfExists('overhead_costs');
        Schema::dropIfExists('cost_categories');
    }
};
