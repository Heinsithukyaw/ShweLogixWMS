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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_code')->unique();
            $table->string('category_name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('hierarchy_level')->nullable();
            $table->string('applicable_industry');
            $table->string('storage_condition');
            $table->string('handling_instructions');
            $table->string('tax_category');
            $table->foreignId('uom_id')->constrained('unit_of_measures')->onDelete('cascade');
            $table->tinyInteger('status')->default(1)->comment('0 - inactive / 1 - active');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
