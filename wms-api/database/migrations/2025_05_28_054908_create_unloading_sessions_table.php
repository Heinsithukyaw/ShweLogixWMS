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
        Schema::create('unloading_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('unloading_session_code');
            $table->foreignId('inbound_shipment_id')->constrained('inbound_shipments')->onDelete('cascade');
            $table->foreignId('dock_id')->constrained('dock_equipment')->onDelete('cascade');
            $table->date('start_time')->nullable();
            $table->date('end_time')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0 - in progress / 1 - planned / 2 - completed');
            $table->foreignId('supervisor_id')->constrained('employees')->onDelete('cascade');
            $table->integer('total_pallets_unloaded')->nullable();
            $table->integer('total_items_unloaded')->nullable();
            $table->string('equipment_used')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unloading_sessions');
    }
};
