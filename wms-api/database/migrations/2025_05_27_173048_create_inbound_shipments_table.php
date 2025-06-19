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
        Schema::create('inbound_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_code');
            $table->foreignId('supplier_id')->constrained('business_parties')->onDelete('cascade');
            $table->foreignId('carrier_id')->constrained('shipping_carriers')->onDelete('cascade');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('staging_location_id')->nullable();
            $table->date('expected_arrival')->nullable();
            $table->date('actual_arrival')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0 - expected / 1 - In Transit / 2 - arrival / 3 - unloaded / 4 - received');
            $table->tinyInteger('version_control')->default(0)->comment('0 - Lite / 1 - Pro / 2 - Legend');
            $table->string('trailer_number')->nullable();
            $table->string('seal_number')->nullable();
            $table->integer('total_pallet')->default(0)->nullable();
            $table->string('total_weight')->default(0)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_shipments');
    }
};
