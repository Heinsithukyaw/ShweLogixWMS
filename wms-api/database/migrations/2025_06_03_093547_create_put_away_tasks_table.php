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
        Schema::create('put_away_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('put_away_task_code');
            $table->foreignId('inbound_shipment_detail_id')->constrained('inbound_shipment_details')->onDelete('cascade');
            $table->foreignId('assigned_to_id')->constrained('employees')->onDelete('cascade');
            $table->date('created_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('start_time')->nullable();
            $table->date('complete_time')->nullable();
            $table->foreignId('source_location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('destination_location_id')->constrained('locations')->onDelete('cascade');
            $table->integer('qty')->nullable();
            $table->tinyInteger('priority')->default(0)->comment('0 - low / 1 - medium / 2 - high');
            $table->tinyInteger('status')->default(0)->comment('0 - pending / 1 - in progress / 2 - completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('put_away_tasks');
    }
};
