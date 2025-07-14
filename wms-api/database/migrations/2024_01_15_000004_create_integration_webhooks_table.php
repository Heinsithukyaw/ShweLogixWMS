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
        Schema::create('integration_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('integration_type');
            $table->string('provider');
            $table->string('event_type'); // order_created, product_updated, etc.
            $table->string('webhook_id')->nullable(); // External webhook ID
            $table->text('webhook_url');
            $table->string('method')->default('POST');
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->string('signature')->nullable();
            $table->boolean('signature_verified')->default(false);
            $table->string('status'); // pending, processing, processed, failed
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamp('next_retry_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['integration_type', 'provider']);
            $table->index(['event_type', 'created_at']);
            $table->index(['status', 'next_retry_at']);
            $table->index('webhook_id');
            $table->index('idempotency_key');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_webhooks');
    }
};