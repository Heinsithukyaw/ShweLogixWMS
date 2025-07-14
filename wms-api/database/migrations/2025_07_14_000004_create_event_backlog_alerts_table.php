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
        Schema::create('event_backlog_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('queue_name');
            $table->integer('queue_size');
            $table->string('severity'); // 'warning', 'critical'
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();
            
            // Add indexes for efficient querying
            $table->index('queue_name');
            $table->index('severity');
            $table->index('is_resolved');
            $table->index('detected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_backlog_alerts');
    }
};