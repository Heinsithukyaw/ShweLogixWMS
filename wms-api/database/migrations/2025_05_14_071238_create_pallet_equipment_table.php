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
        Schema::create('pallet_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('pallet_code');
            $table->string('pallet_name');
            $table->string('pallet_type');
            $table->string('material');
            $table->string('manufacturer')->nullable();
            $table->string('length')->nullable();
            $table->string('width')->nullable();
            $table->string('height')->nullable();
            $table->string('weight_capacity')->nullable();
            $table->string('empty_weight')->nullable();
            $table->tinyInteger('condition')->nullable()->comment(' 0 - good / 1 - excellent / 2 - fair / 3 - poor / 4 - damaged ');
            $table->string('current_location')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('last_inspection_date')->nullable();
            $table->date('next_inspection_date')->nullable();
            $table->tinyInteger('pooled_pallet')->nullable()->comment('0 - no / 1 - yes');
            $table->string('pool_provider')->nullable();
            $table->string('cost_per_unit')->nullable();
            $table->integer('expected_lifespan_year')->nullable();
            $table->string('rfid_tag')->nullable();
            $table->string('barcode')->nullable();
            $table->string('currently_assigned')->nullable();
            $table->string('assigned_shipment')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0 - available / 1 - in use / 2 - reserved / 3 - under repair / 4 - Quarantined / 5 - disposed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pallet_equipment');
    }
};
