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
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->string('uom_code');
            $table->string('uom_name');
            $table->foreignId('base_uom_id')->constrained('base_uoms')->onDelete('cascade');
            $table->string('conversion_factor');
            $table->text('description')->nullable();
            $table->tinyInteger('status')->comment('0 - inactive / 1 - active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
