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
        Schema::create('receiving_docks', function (Blueprint $table) {
            $table->id();
            $table->string('dock_code');
            $table->string('dock_number');
            $table->string('dock_type');
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->tinyInteger('status')->default(2)->comment('0 - out of service / 1 - in used / 2 - available');
            $table->string('features')->nullable();
            $table->text('additional_features')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiving_docks');
    }
};
