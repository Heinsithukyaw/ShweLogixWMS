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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->bigInteger('customer_id')->unsigned();
            $table->date('order_date');
            $table->date('ship_date')->nullable();
            $table->enum('status', ['pending', 'allocated', 'picking', 'packed', 'shipped', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('shipment_method')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('created_by')->nullable();
            $table->string('last_modified_by')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('business_parties')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
}; 