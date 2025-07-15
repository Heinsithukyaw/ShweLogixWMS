<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Order Fulfillments
        Schema::create('order_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->onDelete('cascade');
            $table->string('fulfillment_status')->default('pending');
            $table->string('fulfillment_type');
            $table->enum('priority_level', ['low', 'medium', 'high', 'urgent']);
            $table->datetime('estimated_ship_date')->nullable();
            $table->datetime('actual_ship_date')->nullable();
            $table->string('tracking_number')->nullable();
            $table->foreignId('shipping_carrier_id')->nullable()->constrained();
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->json('automation_rules')->nullable();
            $table->text('fulfillment_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['fulfillment_status', 'priority_level']);
            $table->index('estimated_ship_date');
        });

        // Order Fulfillment Items
        Schema::create('order_fulfillment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_fulfillment_id')->constrained()->onDelete('cascade');
            $table->foreignId('sales_order_item_id')->constrained();
            $table->foreignId('product_id')->nullable()->constrained();
            $table->decimal('quantity_ordered', 10, 2);
            $table->decimal('quantity_fulfilled', 10, 2)->default(0);
            $table->decimal('quantity_remaining', 10, 2);
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('volume', 10, 3)->nullable();
            $table->string('pick_location')->nullable();
            $table->string('fulfillment_status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Order Fulfillment History
        Schema::create('order_fulfillment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_fulfillment_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->timestamp('changed_at');
            $table->timestamps();
        });

        // Inventory Syncs
        Schema::create('inventory_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->string('platform');
            $table->string('platform_product_id');
            $table->decimal('wms_quantity', 10, 2)->default(0);
            $table->decimal('platform_quantity', 10, 2)->default(0);
            $table->string('sync_status')->default('pending');
            $table->timestamp('last_sync_at')->nullable();
            $table->enum('sync_frequency', ['real_time', 'every_15_minutes', 'hourly', 'daily']);
            $table->json('sync_rules')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'platform']);
            $table->index(['sync_status', 'next_sync_at']);
        });

        // Return Orders
        Schema::create('return_orders', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('original_order_id')->constrained('sales_orders');
            $table->foreignId('customer_id')->constrained('business_parties');
            $table->string('return_reason');
            $table->enum('return_type', ['refund', 'exchange', 'store_credit']);
            $table->string('return_status')->default('pending');
            $table->timestamp('requested_date');
            $table->timestamp('approved_date')->nullable();
            $table->timestamp('received_date')->nullable();
            $table->timestamp('processed_date')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->decimal('restocking_fee', 10, 2)->default(0);
            $table->decimal('return_shipping_cost', 10, 2)->default(0);
            $table->text('inspection_notes')->nullable();
            $table->text('processing_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['return_status', 'return_type']);
        });

        // Return Order Items
        Schema::create('return_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('original_order_item_id')->constrained('sales_order_items');
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity_returned', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->string('return_reason');
            $table->enum('condition_received', ['new', 'like_new', 'good', 'fair', 'poor', 'damaged'])->nullable();
            $table->enum('disposition', ['restock', 'resell', 'donate', 'dispose', 'return_to_vendor'])->nullable();
            $table->text('inspection_notes')->nullable();
            $table->boolean('restockable')->default(false);
            $table->timestamps();
        });

        // Return Order History
        Schema::create('return_order_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_order_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->timestamp('changed_at');
            $table->timestamps();
        });

        // Shipping Cost Tracking
        Schema::create('shipping_cost_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained();
            $table->foreignId('shipping_carrier_id')->constrained();
            $table->string('service_type');
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->decimal('cost_variance', 10, 2)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->json('dimensions')->nullable();
            $table->decimal('distance', 10, 2)->nullable();
            $table->string('zone')->nullable();
            $table->decimal('fuel_surcharge', 10, 2)->default(0);
            $table->decimal('additional_fees', 10, 2)->default(0);
            $table->decimal('discount_applied', 10, 2)->default(0);
            $table->string('tracking_number')->nullable();
            $table->string('cost_calculation_method')->nullable();
            $table->json('cost_factors')->nullable();
            $table->timestamps();

            $table->index(['shipping_carrier_id', 'service_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping_cost_tracking');
        Schema::dropIfExists('return_order_history');
        Schema::dropIfExists('return_order_items');
        Schema::dropIfExists('return_orders');
        Schema::dropIfExists('inventory_syncs');
        Schema::dropIfExists('order_fulfillment_history');
        Schema::dropIfExists('order_fulfillment_items');
        Schema::dropIfExists('order_fulfillments');
    }
};