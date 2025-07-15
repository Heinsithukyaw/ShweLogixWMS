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
        Schema::create('storage_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('storage_equipment_code');
            $table->string('storage_equipment_name');
            $table->string('storage_equipment_type');
            $table->string('manufacturer');
            $table->string('model');
            $table->string('serial_number');
            $table->date('purchase_date');
            $table->date('warranty_expire_date');
            // $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade')->nullable();
            $table->string('aisle')->nullable();
            $table->string('bay')->nullable();
            $table->string('level')->nullable();
            $table->date('installation_date')->nullable();
            $table->date('last_inspection_date')->nullable();
            $table->date('next_inspection_due_date')->nullable();
            $table->string('inspection_frequency')->nullable();
            $table->string('max_weight_capacity')->nullable();
            $table->string('max_volume_capacity')->nullable();
            $table->string('length')->nullable();
            $table->string('width')->nullable();
            $table->string('height')->nullable();
            $table->string('material')->nullable();
            $table->string('shelves_tiers_number')->nullable();
            $table->string('adjustability')->default(1)->nullable()->comment('1 - fixed / 2 - fixed lanes / 3 - adjustable beans / 4 - adjustable shelves / 5 - cart & carrier System');
            $table->string('safety_features')->nullable()->comment('');
            $table->string('load_type')->nullable();
            $table->string('accessibility')->nullable();
            $table->string('uptime_percentage_monthly')->nullable();
            $table->string('maintenance_cost')->nullable();
            $table->string('currency_unit')->nullable();
            $table->date('depreciation_start_date')->nullable();
            $table->string('depreciation_method')->nullable();
            $table->string('estimated_useful_life_year')->nullable();
            $table->foreignId('supplier_id')->constrained('business_parties')->onDelete('cascade')->nullable();
            $table->date('expected_replacement_date')->nullable();
            $table->string('disposal_date')->nullable();
            $table->string('replacement_mhe_code')->nullable();
            $table->text('remark')->nullable();
            $table->text('custom_attributes')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1 - operational / 2 - under maintenance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_equipment');
    }
};
