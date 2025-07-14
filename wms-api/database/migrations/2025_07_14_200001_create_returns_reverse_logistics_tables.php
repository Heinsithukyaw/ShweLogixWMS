<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Return Authorization (RMA) table
        Schema::create('return_authorizations', function (Blueprint $table) {
            $table->id();
            $table->string('rma_number')->unique();
            $table->foreignId('customer_id')->constrained('business_parties');
            $table->foreignId('original_order_id')->nullable()->constrained('sales_orders');
            $table->enum('return_type', ['defective', 'damaged', 'wrong_item', 'customer_change', 'warranty', 'recall']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'in_transit', 'received', 'processed', 'completed', 'cancelled']);
            $table->text('reason')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->decimal('actual_refund_amount', 15, 2)->default(0);
            $table->date('requested_date');
            $table->date('approved_date')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('processed_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->json('return_shipping_info')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'warehouse_id']);
            $table->index(['customer_id', 'requested_date']);
        });

        // Return Authorization Items
        Schema::create('return_authorization_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_authorization_id')->constrained('return_authorizations')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('original_order_item_id')->nullable()->constrained('sales_order_items');
            $table->integer('requested_quantity');
            $table->integer('approved_quantity')->default(0);
            $table->integer('received_quantity')->default(0);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_value', 15, 2);
            $table->enum('condition_expected', ['new', 'used', 'damaged', 'defective']);
            $table->enum('condition_actual', ['new', 'used', 'damaged', 'defective'])->nullable();
            $table->text('item_notes')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        // Return Receipts
        Schema::create('return_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('return_authorization_id')->constrained('return_authorizations');
            $table->foreignId('received_by')->constrained('users');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->datetime('received_at');
            $table->enum('overall_condition', ['excellent', 'good', 'fair', 'poor', 'damaged']);
            $table->text('inspection_notes')->nullable();
            $table->json('photos')->nullable(); // Store photo URLs
            $table->boolean('quality_check_required')->default(false);
            $table->boolean('quality_check_completed')->default(false);
            $table->foreignId('quality_checked_by')->nullable()->constrained('users');
            $table->datetime('quality_checked_at')->nullable();
            $table->timestamps();
        });

        // Return Receipt Items
        Schema::create('return_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_receipt_id')->constrained('return_receipts')->onDelete('cascade');
            $table->foreignId('return_authorization_item_id')->constrained('return_authorization_items');
            $table->foreignId('product_id')->constrained('products');
            $table->integer('received_quantity');
            $table->enum('condition', ['new', 'used', 'damaged', 'defective']);
            $table->enum('disposition', ['restock', 'refurbish', 'scrap', 'return_to_vendor', 'donate']);
            $table->text('inspection_notes')->nullable();
            $table->decimal('restocking_fee', 10, 2)->default(0);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->string('serial_number')->nullable();
            $table->string('batch_number')->nullable();
            $table->timestamps();
        });

        // Reverse Logistics Orders
        Schema::create('reverse_logistics_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->enum('type', ['return_to_vendor', 'disposal', 'donation', 'refurbishment', 'recycling']);
            $table->enum('status', ['pending', 'approved', 'in_progress', 'shipped', 'completed', 'cancelled']);
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('vendor_id')->nullable()->constrained('business_parties');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('description')->nullable();
            $table->text('special_instructions')->nullable();
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->json('shipping_info')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'status']);
            $table->index(['warehouse_id', 'scheduled_date']);
        });

        // Reverse Logistics Order Items
        Schema::create('reverse_logistics_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reverse_logistics_order_id')->constrained('reverse_logistics_orders')->onDelete('cascade');
            $table->foreignId('return_receipt_item_id')->nullable()->constrained('return_receipt_items');
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity');
            $table->enum('condition', ['new', 'used', 'damaged', 'defective']);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('batch_number')->nullable();
            $table->timestamps();
        });

        // Refurbishment Tasks
        Schema::create('refurbishment_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_number')->unique();
            $table->foreignId('return_receipt_item_id')->constrained('return_receipt_items');
            $table->foreignId('product_id')->constrained('products');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'cancelled']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->text('work_description');
            $table->text('completion_notes')->nullable();
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->decimal('actual_cost', 10, 2)->default(0);
            $table->integer('estimated_hours')->default(0);
            $table->integer('actual_hours')->default(0);
            $table->date('scheduled_date')->nullable();
            $table->date('started_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->json('required_parts')->nullable();
            $table->json('used_parts')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'scheduled_date']);
        });

        // Return Analytics
        Schema::create('return_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('analytics_date');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->string('return_reason')->nullable();
            $table->integer('total_returns')->default(0);
            $table->integer('total_items')->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->decimal('restocking_fees', 15, 2)->default(0);
            $table->decimal('processing_cost', 15, 2)->default(0);
            $table->integer('restocked_items')->default(0);
            $table->integer('refurbished_items')->default(0);
            $table->integer('scrapped_items')->default(0);
            $table->decimal('recovery_rate', 5, 2)->default(0); // Percentage
            $table->timestamps();
            
            $table->unique(['analytics_date', 'warehouse_id', 'product_id', 'return_reason']);
            $table->index(['analytics_date', 'warehouse_id']);
        });

        // Return Policies
        Schema::create('return_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_name');
            $table->foreignId('customer_id')->nullable()->constrained('business_parties');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->integer('return_window_days')->default(30);
            $table->boolean('restocking_fee_applicable')->default(false);
            $table->decimal('restocking_fee_percentage', 5, 2)->default(0);
            $table->decimal('restocking_fee_fixed', 10, 2)->default(0);
            $table->json('allowed_return_reasons')->nullable();
            $table->json('required_conditions')->nullable();
            $table->boolean('requires_original_packaging')->default(false);
            $table->boolean('requires_receipt')->default(true);
            $table->boolean('auto_approve')->default(false);
            $table->text('terms_and_conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['customer_id', 'is_active']);
            $table->index(['product_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_policies');
        Schema::dropIfExists('return_analytics');
        Schema::dropIfExists('refurbishment_tasks');
        Schema::dropIfExists('reverse_logistics_order_items');
        Schema::dropIfExists('reverse_logistics_orders');
        Schema::dropIfExists('return_receipt_items');
        Schema::dropIfExists('return_receipts');
        Schema::dropIfExists('return_authorization_items');
        Schema::dropIfExists('return_authorizations');
    }
};