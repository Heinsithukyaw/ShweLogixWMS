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
        Schema::create('pick_waves', function (Blueprint $table) {
            $table->id();
            $table->string('wave_number')->unique();
            $table->date('wave_date');
            $table->enum('status', ['planned', 'released', 'picking', 'completed', 'cancelled'])->default('planned');
            $table->integer('total_orders')->default(0);
            $table->integer('total_items')->default(0);
            $table->bigInteger('assigned_to')->unsigned()->nullable();
            $table->timestamp('planned_start_time')->nullable();
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('planned_completion_time')->nullable();
            $table->timestamp('actual_completion_time')->nullable();
            $table->text('notes')->nullable();
            $table->string('pick_strategy')->default('discrete'); // discrete, batch, zone, cluster
            $table->string('created_by')->nullable();
            $table->string('last_modified_by')->nullable();
            $table->timestamps();

            $table->foreign('assigned_to')->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pick_waves');
    }
}; 