<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Order Allocations
        Schema::create('order_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('sales_order_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('location_id')->constrained();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('allocated_quantity', 10, 3);
            $table->decimal('picked_quantity', 10, 3)->default(0);
            $table->enum('allocation_status', ['allocated', 'partially_picked', 'picked', 'cancelled']);
            $table->enum('allocation_type', ['fifo', 'lifo', 'fefo', 'manual']);
            $table->timestamp('allocated_at');
            $table->timestamp('expires_at')->nullable();
            $table->json('allocation_rules')->nullable();
            $table->foreignId('allocated_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['sales_order_id', 'allocation_status']);
            $table->index(['product_id', 'location_id']);
            $table->index(['lot_number', 'serial_number']);
        });

        // Order Priorities
        Schema::create('order_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->onDelete('cascade');
            $table->integer('priority_score')->default(100);
            $table->enum('priority_level', ['low', 'normal', 'high', 'urgent', 'critical']);
            $table->json('priority_factors')->nullable(); // customer_tier, order_value, ship_date, etc.
            $table->timestamp('priority_calculated_at');
            $table->string('priority_reason')->nullable();
            $table->boolean('is_manual_override')->default(false);
            $table->foreignId('set_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['priority_level', 'priority_score']);
            $table->index('priority_calculated_at');
        });

        // Back Orders
        Schema::create('back_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('sales_order_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->decimal('backordered_quantity', 10, 3);
            $table->decimal('fulfilled_quantity', 10, 3)->default(0);
            $table->enum('backorder_status', ['pending', 'partially_fulfilled', 'fulfilled', 'cancelled']);
            $table->date('expected_fulfillment_date')->nullable();
            $table->text('backorder_reason');
            $table->json('fulfillment_options')->nullable();
            $table->boolean('auto_fulfill')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['backorder_status', 'expected_fulfillment_date']);
            $table->index(['product_id', 'backorder_status']);
        });

        // Order Consolidations
        Schema::create('order_consolidations', function (Blueprint $table) {
            $table->id();
            $table->string('consolidation_number')->unique();
            $table->json('sales_order_ids'); // Array of order IDs
            $table->foreignId('customer_id')->constrained('business_parties');
            $table->string('shipping_address_hash'); // Hash of shipping address
            $table->enum('consolidation_status', ['pending', 'processing', 'completed', 'cancelled']);
            $table->enum('consolidation_type', ['customer', 'address', 'route', 'manual']);
            $table->json('consolidation_rules')->nullable();
            $table->decimal('total_weight', 8, 3)->nullable();
            $table->decimal('total_volume', 8, 3)->nullable();
            $table->integer('total_items');
            $table->timestamp('consolidation_deadline')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['customer_id', 'consolidation_status']);
            $table->index('consolidation_deadline');
        });

        // Order Splits
        Schema::create('order_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_order_id')->constrained('sales_orders')->onDelete('cascade');
            $table->foreignId('split_order_id')->constrained('sales_orders')->onDelete('cascade');
            $table->enum('split_reason', ['inventory_shortage', 'shipping_constraints', 'customer_request', 'manual']);
            $table->enum('split_type', ['partial_shipment', 'backorder', 'expedite']);
            $table->json('split_items'); // Items moved to split order
            $table->text('split_notes')->nullable();
            $table->foreignId('split_by')->constrained('users');
            $table->timestamp('split_at');
            $table->timestamps();
            
            $table->index(['original_order_id', 'split_reason']);
        });

        // Order Holds
        Schema::create('order_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->onDelete('cascade');
            $table->enum('hold_type', ['credit', 'inventory', 'quality', 'customer', 'compliance', 'manual']);
            $table->enum('hold_status', ['active', 'released', 'expired']);
            $table->text('hold_reason');
            $table->json('hold_conditions')->nullable(); // Conditions to release hold
            $table->timestamp('hold_placed_at');
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('hold_released_at')->nullable();
            $table->foreignId('placed_by')->constrained('users');
            $table->foreignId('released_by')->nullable()->constrained('users');
            $table->text('release_notes')->nullable();
            $table->timestamps();
            
            $table->index(['sales_order_id', 'hold_status']);
            $table->index(['hold_type', 'hold_status']);
        });

        // Order Modifications
        Schema::create('order_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->onDelete('cascade');
            $table->enum('modification_type', ['item_add', 'item_remove', 'quantity_change', 'address_change', 'date_change', 'priority_change']);
            $table->json('original_data'); // Original values
            $table->json('modified_data'); // New values
            $table->enum('modification_status', ['pending', 'approved', 'rejected', 'applied']);
            $table->text('modification_reason');
            $table->json('impact_analysis')->nullable(); // Cost, timing impact
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            
            $table->index(['sales_order_id', 'modification_status']);
            $table->index('modification_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_modifications');
        Schema::dropIfExists('order_holds');
        Schema::dropIfExists('order_splits');
        Schema::dropIfExists('order_consolidations');
        Schema::dropIfExists('back_orders');
        Schema::dropIfExists('order_priorities');
        Schema::dropIfExists('order_allocations');
    }
};