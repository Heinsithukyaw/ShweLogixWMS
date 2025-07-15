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
        Schema::create('advanced_shipping_notices', function (Blueprint $table) {
            $table->id();
            $table->string('asn_code');
            $table->foreignId('supplier_id')->constrained('business_parties')->onDelete('cascade');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->date('expected_arrival')->nullable();
            $table->foreignId('carrier_id')->constrained('shipping_carriers')->onDelete('cascade');
            $table->string('tracking_number')->nullable();
            $table->integer('total_items')->default(0)->nullable();
            $table->integer('total_pallet')->default(0)->nullable();
            $table->tinyInteger('status')->default(0)->comment('0 - pending / 1 - verified / 2 - received');
            $table->text('notes')->nullable();
            $table->date('received_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advanced_shipping_notices');
    }
};
