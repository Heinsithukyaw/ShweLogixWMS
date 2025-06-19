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
        Schema::create('cross_docking_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('cross_docking_task_code');
            $table->foreignId('asn_id')->constrained('advanced_shipping_notices')->onDelete('cascade');
            $table->foreignId('asn_detail_id')->constrained('advanced_shipping_notice_details')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('products')->onDelete('cascade');
            $table->text('item_description')->nullable();
            $table->integer('qty')->nullable();
            $table->foreignId('source_location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('destination_location_id')->constrained('locations')->onDelete('cascade');
            $table->unsignedBigInteger('outbound_shipment_id')->nullable();
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->tinyInteger('priority')->default(0)->comment('0 - low / 1 - medium / 2 - high');
            $table->tinyInteger('status')->default(0)->comment('0 - pending / 1 - in progress / 2 - completed / 3 -delayed');
            $table->date('created_date')->nullable();
            $table->date('start_time')->nullable();
            $table->date('complete_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cross_docking_tasks');
    }
};
