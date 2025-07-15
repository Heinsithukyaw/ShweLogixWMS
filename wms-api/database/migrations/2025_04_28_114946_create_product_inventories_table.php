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
        Schema::create('product_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('uom_id')->constrained('unit_of_measures')->onDelete('cascade');
            $table->string('warehouse_code')->nullable();
            $table->string('location')->nullable();
            $table->string('batch_no');
            $table->string('lot_no');
            $table->integer('packing_qty')->nullable();
            $table->integer('whole_qty')->nullable();
            $table->integer('loose_qty')->nullable();
            $table->integer('reorder_level')->nullable();
            $table->string('stock_rotation_policy')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_inventories');
    }
};
