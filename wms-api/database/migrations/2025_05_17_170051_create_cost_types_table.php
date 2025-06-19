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
        Schema::create('cost_types', function (Blueprint $table) {
            $table->id();
            $table->string('cost_code');
            $table->string('cost_name');
            $table->string('cost_type');
            $table->foreignId('category_id')->constrained('financial_categories')->onDelete('cascade');
            $table->foreignId('subcategory_id')->constrained('financial_categories')->onDelete('cascade');
            $table->string('created_by')->nullable();
            $table->string('modified_by')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0 - inactive / 1 - active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_types');
    }
};
