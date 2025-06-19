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
        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('quality_inspection_code');
            $table->foreignId('inbound_shipment_detail_id')->constrained('inbound_shipment_details')->onDelete('cascade');
            $table->string('inspector_name');
            $table->date('inspection_date')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0 - pending / 1 - failed / 2 - passed');
            $table->text('rejection_reason')->nullable();
            $table->integer('sample_size')->nullable();
            $table->text('notes')->nullable();
            $table->text('corrective_action')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_inspections');
    }
};
