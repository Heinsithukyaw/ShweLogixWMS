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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('tax_code');
            $table->string('tax_description');
            $table->string('tax_type');
            $table->string('tax_rate');
            $table->date('effective_date');
            $table->string('tax_calculation_method');
            $table->string('tax_authority');
            $table->text('notes')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0 - inactive / 1 - active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
