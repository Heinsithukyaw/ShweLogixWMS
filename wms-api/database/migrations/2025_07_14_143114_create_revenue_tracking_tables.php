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
        // Storage Revenue Categories
        Schema::create('revenue_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Storage Revenue Rates
        Schema::create('storage_revenue_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revenue_category_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->decimal('rate_per_unit', 15, 2);
            $table->string('unit_type'); // sqft, cbm, pallet position
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('revenue_category_id')->references('id')->on('revenue_categories');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('set null');
        });
        
        // Handling Revenue Rates
        Schema::create('handling_revenue_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revenue_category_id');
            $table->string('activity_type'); // receiving, putaway, picking, packing, shipping
            $table->decimal('rate_per_unit', 15, 2);
            $table->string('unit_type'); // per item, per carton, per pallet, per order
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('business_party_id')->nullable(); // specific client
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('revenue_category_id')->references('id')->on('revenue_categories');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('business_party_id')->references('id')->on('business_parties')->onDelete('set null');
        });
        
        // Revenue Transactions
        Schema::create('revenue_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revenue_category_id');
            $table->unsignedBigInteger('business_party_id');
            $table->string('transaction_type'); // storage, handling, value-added, other
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->string('invoice_number')->nullable();
            $table->string('payment_status')->default('pending'); // pending, paid, cancelled
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('revenue_category_id')->references('id')->on('revenue_categories');
            $table->foreign('business_party_id')->references('id')->on('business_parties');
        });
        
        // Billing Rates
        Schema::create('billing_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('business_party_id')->nullable(); // if null, applies to all clients
            $table->string('service_type'); // storage, receiving, shipping, etc.
            $table->decimal('rate', 15, 2);
            $table->string('unit'); // per pallet, per item, per hour, etc.
            $table->decimal('minimum_charge', 15, 2)->nullable();
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('business_party_id')->references('id')->on('business_parties')->onDelete('set null');
        });
        
        // Revenue Reports
        Schema::create('revenue_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type'); // daily, weekly, monthly, quarterly, yearly
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_revenue', 15, 2);
            $table->decimal('storage_revenue', 15, 2)->default(0);
            $table->decimal('handling_revenue', 15, 2)->default(0);
            $table->decimal('value_added_revenue', 15, 2)->default(0);
            $table->decimal('other_revenue', 15, 2)->default(0);
            $table->unsignedBigInteger('business_party_id')->nullable(); // if null, covers all clients
            $table->unsignedBigInteger('warehouse_id')->nullable(); // if null, covers all warehouses
            $table->string('status'); // draft, final
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('business_party_id')->references('id')->on('business_parties')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_reports');
        Schema::dropIfExists('billing_rates');
        Schema::dropIfExists('revenue_transactions');
        Schema::dropIfExists('handling_revenue_rates');
        Schema::dropIfExists('storage_revenue_rates');
        Schema::dropIfExists('revenue_categories');
    }
};
