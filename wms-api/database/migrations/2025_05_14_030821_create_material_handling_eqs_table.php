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
        Schema::create('material_handling_eqs', function (Blueprint $table) {
            $table->id();
            $table->string('mhe_code');
            $table->string('mhe_name');
            $table->string('mhe_type');
            $table->string('manufacturer');
            $table->string('model');
            $table->string('serial_number');
            $table->date('purchase_date');
            $table->date('warranty_expire_date');
            $table->string('capacity');
            $table->string('capacity_unit');
            $table->string('current_location_detail')->nullable();
            $table->string('home_location')->nullable();
            $table->string('shift_availability')->nullable();
            $table->string('operator_assigned')->nullable();
            $table->string('maintenance_schedule_type')->nullable();
            $table->string('maintenance_frequency')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->string('last_service_type')->nullable();
            $table->date('last_maintenance_due_date')->nullable();
            $table->date('safety_inspection_due_date')->nullable();
            $table->date('safety_certification_expire_date')->nullable();
            $table->string('safety_features')->nullable();
            $table->string('uptime_percentage_monthly')->nullable();
            $table->string('maintenance_cost')->nullable();
            $table->string('currency')->nullable();
            $table->string('currency_unit')->nullable();
            $table->string('energy_consumption_per_hour')->nullable();
            $table->date('depreciation_start_date')->nullable();
            $table->string('depreciation_method')->nullable();
            $table->string('estimated_useful_life_year')->nullable();
            $table->foreignId('supplier_id')->constrained('business_parties')->onDelete('cascade')->nullable();
            $table->foreignId('supplier_contact_id')->constrained('business_contacts')->onDelete('cascade')->nullable();
            $table->date('expected_replacement_date')->nullable();
            $table->string('disposal_date')->nullable();
            $table->unsignedBigInteger('replacement_mhe_id')->nullable();
            $table->text('remark')->nullable();
            $table->text('custom_attributes')->nullable();
            $table->tinyInteger('usage_status')->default(1)->comment('1 - available / 2 - maintenance / 3 - in use');
            $table->tinyInteger('status')->default(1)->comment('1 - operational / 2 - under maintenance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_handling_eqs');
    }
};
