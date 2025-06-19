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
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('area_code');
            $table->string('area_name');
            $table->string('area_type');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->text('responsible_person')->nullable();           
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('location_description')->nullable();
            $table->string('capacity')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('environmental_conditions')->nullable();
            $table->string('equipment')->nullable();
            $table->text('custom_attributes')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0 - inactive / 1 - active / 2 - Under Maintenance / 3 - Planned / 4 - Decommissioned');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
