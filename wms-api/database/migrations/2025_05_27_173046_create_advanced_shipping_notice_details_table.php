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
        Schema::create('advanced_shipping_notice_details', function (Blueprint $table) {
            $table->id();
            $table->string('asn_detail_code');
            $table->foreignId('asn_id')->constrained('advanced_shipping_notices')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('products')->onDelete('cascade');
            $table->text('item_description')->nullable();
            $table->integer('expected_qty')->default(0)->nullable();
            $table->foreignId('uom_id')->constrained('unit_of_measures')->onDelete('cascade');
            $table->string('lot_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->integer('received_qty')->nullable();
            $table->string('variance')->default(0)->nullable();
            $table->tinyInteger('status')->default(0)->comment('0 - pending / 1 - missing / 2 - partial / 3 - received');
            // $table->foreignId('location_id')->constrained('locations')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('pallet_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advanced_shipping_notice_details');
    }
};
