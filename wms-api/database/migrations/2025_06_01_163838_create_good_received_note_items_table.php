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
        Schema::create('good_received_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('good_received_notes')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('expected_qty')->nullable();
            $table->integer('received_qty')->nullable();
            $table->foreignId('uom_id')->constrained('unit_of_measures')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->tinyInteger('condition_status')->default(0)->comment('0 - damaged / 1 - expired / 2 - good');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('good_received_note_items');
    }
};
