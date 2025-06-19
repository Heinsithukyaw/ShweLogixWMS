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
        Schema::create('receiving_labor_trackings', function (Blueprint $table) {
            $table->id();
            $table->string('labor_entry_code'); 
            $table->foreignId('emp_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('inbound_shipment_id')->constrained('inbound_shipments')->onDelete('cascade');
            $table->string('task_type');
            $table->date('start_time')->nullable();
            $table->date('end_time')->nullable();
            $table->integer('duration_min')->nullable();
            $table->integer('items_processed')->nullable();
            $table->integer('pallets_processed')->nullable();
            $table->string('items_min')->nullable();
            $table->text('notes')->nullable();
            $table->tinyInteger('version_control')->default(0)->comment('0 - lite / 1 - pro / 2 - legend');
            $table->tinyInteger('status')->default(0)->comment('0 - active / 1 - in active')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiving_labor_trackings');
    }
};
