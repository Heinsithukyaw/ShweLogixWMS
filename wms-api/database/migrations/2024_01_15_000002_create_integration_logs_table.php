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
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('integration_type');
            $table->string('provider');
            $table->string('operation'); // sync, webhook, api_call, etc.
            $table->string('method')->nullable(); // GET, POST, PUT, DELETE
            $table->text('endpoint')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->json('headers')->nullable();
            $table->string('status'); // success, error, pending, timeout
            $table->integer('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->integer('retry_count')->default(0);
            $table->string('idempotency_key')->nullable();
            $table->string('correlation_id')->nullable();
            $table->string('user_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['integration_type', 'provider']);
            $table->index(['status', 'created_at']);
            $table->index('operation');
            $table->index('idempotency_key');
            $table->index('correlation_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};