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
        Schema::create('inbound_shipment_details', function (Blueprint $table) {
            $table->id();
            $table->string('inbound_detail_code');
            $table->foreignId('inbound_shipment_id')->constrained('inbound_shipments')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('purchase_order_number')->nullable();
            $table->integer('expected_qty')->nullable();
            $table->integer('received_qty')->nullable();
            $table->integer('damaged_qty')->nullable();
            $table->string('lot_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->string('received_by')->nullable();
            $table->date('received_date')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0 - exception / 1 - expected / 2 - received');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_shipment_details');
    }
};
