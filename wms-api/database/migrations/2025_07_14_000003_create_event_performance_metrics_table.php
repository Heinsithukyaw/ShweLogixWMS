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
        Schema::create('event_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->timestamp('measured_at');
            $table->decimal('processing_time', 10, 6); // In seconds
            $table->string('status')->default('success'); // 'success', 'error', 'timeout'
            $table->string('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional metadata about the event processing
            $table->timestamps();
            
            // Add indexes for efficient querying
            $table->index('event_name');
            $table->index('measured_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_performance_metrics');
    }
};