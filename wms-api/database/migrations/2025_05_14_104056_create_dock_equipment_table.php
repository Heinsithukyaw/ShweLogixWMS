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
        Schema::create('dock_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('dock_code');
            $table->string('dock_name');
            $table->string('dock_type');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->string('dock_number');
            $table->string('capacity');
            $table->string('capacity_unit');
            $table->string('dimensions')->nullable();
            $table->text('equipment_features')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->string('assigned_staff')->nullable();
            $table->string('operating_hours')->nullable();
            $table->text('remarks')->nullable();
            $table->text('custom_attributes')->nullable();
            $table->tinyInteger('status')->default(2)->comment('0 - under maintenance / 1 - out of service / 2 - operational / 3 - scheduled maintenance / 4 - reserved');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dock_equipment');
    }
};
