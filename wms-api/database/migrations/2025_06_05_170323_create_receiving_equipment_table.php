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
        Schema::create('receiving_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_equipment_code');
            $table->string('receiving_equipment_name');
            $table->string('receiving_equipment_type');
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->text('notes')->nullable();
            $table->integer('days_since_maintenance')->nullable();
            $table->tinyInteger('version_control')->default(0)->comment('0 - lite / 1 - pro / 2 -legend');
            $table->tinyInteger('status')->default(2)->comment('0 - in use / 1 - maintenance / 2 - available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiving_equipment');
    }
};
