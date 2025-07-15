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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('location_code');
            $table->string('location_name');
            $table->string('location_type');
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->text('aisle')->nullable();
            $table->string('row')->nullable();
            $table->string('level')->nullable();
            $table->string('bin')->nullable();
            $table->integer('capacity')->nullable();
            $table->string('capacity_unit')->nullable();
            $table->string('restrictions')->nullable();
            $table->string('bar_code')->nullable();
            $table->text('description')->nullable();
            $table->integer('utilization')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1 - available / 2 - occupied / 3 - reserved / 4 - under maintenance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
