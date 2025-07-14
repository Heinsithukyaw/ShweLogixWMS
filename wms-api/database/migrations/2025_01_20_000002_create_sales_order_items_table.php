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
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sales_order_id')->unsigned();
            $table->bigInteger('product_id')->unsigned();
            $table->string('item_code');
            $table->text('item_description')->nullable();
            $table->decimal('quantity_ordered', 10, 3)->default(0);
            $table->decimal('quantity_allocated', 10, 3)->default(0);
            $table->decimal('quantity_picked', 10, 3)->default(0);
            $table->decimal('quantity_packed', 10, 3)->default(0);
            $table->decimal('quantity_shipped', 10, 3)->default(0);
            $table->bigInteger('uom_id')->unsigned();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, allocated, picking, picked, packed, shipped
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('unit_of_measures')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
}; 