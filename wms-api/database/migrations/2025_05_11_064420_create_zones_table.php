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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('zone_code');
            $table->string('zone_name');
            $table->string('zone_type');
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->integer('priority')->comment('0 - lower / 1 - higher for picking and putaway logic');
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0 - in active / 1 - active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
