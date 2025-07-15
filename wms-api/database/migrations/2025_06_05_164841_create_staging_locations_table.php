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
        Schema::create('staging_locations', function (Blueprint $table) {
            $table->id();
            $table->string('staging_location_code');
            $table->string('staging_location_name');
            $table->string('type');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');

            $table->integer('capacity')->nullable();
            $table->text('description')->nullable();
            $table->integer('current_usage')->nullable();
            $table->date('last_updated')->nullable();
            $table->tinyInteger('status')->default(2)->comment('0 - in active / 1 - maintenance / 2 - active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staging_locations');
    }
};
