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
        Schema::create('product_commercials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('customer_code');
            $table->string('bar_code');
            $table->string('cost_price');
            $table->string('standard_price');
            $table->string('currency');
            $table->string('discount')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('country_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_commercials');
    }
};
