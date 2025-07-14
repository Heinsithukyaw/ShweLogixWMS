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
        Schema::create('event_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique();
            $table->string('event_name');
            $table->string('event_source');
            $table->json('event_payload');
            $table->string('processing_status')->default('pending'); // 'pending', 'processing', 'completed', 'failed'
            $table->json('processing_result')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            // Add indexes for efficient querying
            $table->index('event_name');
            $table->index('event_source');
            $table->index('processing_status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_idempotency_keys');
    }
};