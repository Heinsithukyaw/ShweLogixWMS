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
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('payment_term_code');
            $table->string('payment_term_name');
            $table->string('payment_type');
            $table->string('payment_due_day')->nullable();
            $table->string('discount_percent')->nullable();
            $table->integer('discount_day')->nullable();
            $table->string('created_by')->nullable();
            $table->string('modified_by')->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0 - inactive / 1 - active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};
