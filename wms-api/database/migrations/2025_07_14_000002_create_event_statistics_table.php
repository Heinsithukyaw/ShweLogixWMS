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
        Schema::create('event_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->string('period_type'); // 'hourly', 'daily', 'monthly'
            $table->string('period_key');  // Format: '2025-07-14' for daily, '2025-07-14:15' for hourly
            $table->integer('count')->default(0);
            $table->decimal('avg_processing_time', 10, 6)->nullable(); // In seconds
            $table->decimal('p50_processing_time', 10, 6)->nullable(); // In seconds
            $table->decimal('p90_processing_time', 10, 6)->nullable(); // In seconds
            $table->decimal('p99_processing_time', 10, 6)->nullable(); // In seconds
            $table->integer('error_count')->default(0);
            $table->timestamps();
            
            // Add indexes for efficient querying
            $table->index('event_name');
            $table->index(['period_type', 'period_key']);
            $table->unique(['event_name', 'period_type', 'period_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_statistics');
    }
};