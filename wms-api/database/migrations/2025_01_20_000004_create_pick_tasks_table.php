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
        Schema::create('pick_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_number')->unique();
            $table->bigInteger('wave_id')->unsigned();
            $table->bigInteger('sales_order_id')->unsigned();
            $table->bigInteger('sales_order_item_id')->unsigned();
            $table->bigInteger('product_id')->unsigned();
            $table->bigInteger('location_id')->unsigned();
            $table->decimal('quantity_requested', 10, 3)->default(0);
            $table->decimal('quantity_picked', 10, 3)->default(0);
            $table->decimal('quantity_short', 10, 3)->default(0);
            $table->bigInteger('assigned_to')->unsigned()->nullable();
            $table->enum('status', ['pending', 'assigned', 'picking', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->timestamp('assigned_time')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('completion_time')->nullable();
            $table->text('notes')->nullable();
            $table->string('pick_method')->default('manual'); // manual, rf, voice, automated
            $table->string('created_by')->nullable();
            $table->string('last_modified_by')->nullable();
            $table->timestamps();

            $table->foreign('wave_id')->references('id')->on('pick_waves')->onDelete('cascade');
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('sales_order_item_id')->references('id')->on('sales_order_items')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pick_tasks');
    }
}; 