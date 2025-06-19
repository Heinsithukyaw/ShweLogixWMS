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
        Schema::create('receiving_appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_code');
            $table->foreignId('inbound_shipment_id')->constrained('inbound_shipments')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('business_parties')->onDelete('cascade');
            $table->foreignId('dock_id')->constrained('dock_equipment')->onDelete('cascade');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->date('start_time')->nullable();
            $table->date('end_time')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0 - scheduled / 1 - confirmed / 2 - in progress / 3 - completed / 4 - cancelled');
            $table->string('carrier_name')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone_number')->nullable();
            $table->string('trailer_number')->nullable();
            $table->integer('estimated_pallet')->nullable();
            $table->date('check_in_time')->nullable();
            $table->date('check_out_time')->nullable();
            $table->tinyInteger('version_control')->default(0)->comment('0 - Lite / 1 - Pro / 2 - Legend')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiving_appointments');
    }
};
