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
        Schema::create('integration_sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('integration_type');
            $table->string('provider');
            $table->string('sync_type'); // full, incremental, delta
            $table->string('data_type'); // products, orders, customers, inventory, etc.
            $table->string('direction'); // inbound, outbound, bidirectional
            $table->string('status'); // pending, running, completed, failed, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->integer('successful_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->integer('skipped_records')->default(0);
            $table->json('sync_filters')->nullable();
            $table->json('sync_settings')->nullable();
            $table->json('error_summary')->nullable();
            $table->text('error_details')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->float('progress_percentage')->default(0);
            $table->string('triggered_by')->nullable(); // user_id, system, schedule, webhook
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['integration_type', 'provider']);
            $table->index(['status', 'created_at']);
            $table->index('data_type');
            $table->index('correlation_id');
            $table->index('started_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_sync_jobs');
    }
};