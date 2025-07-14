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
        Schema::create('integration_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('integration_type'); // erp, ecommerce, marketplace, tms, etc.
            $table->string('provider'); // sap, shopify, amazon, etc.
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->json('configuration'); // Store configuration data
            $table->boolean('is_active')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->string('health_status')->default('unknown'); // healthy, unhealthy, unknown
            $table->json('health_details')->nullable();
            $table->integer('sync_frequency')->default(3600); // seconds
            $table->string('sync_mode')->default('real_time'); // real_time, batch, manual
            $table->json('sync_settings')->nullable();
            $table->json('webhook_settings')->nullable();
            $table->json('rate_limit_settings')->nullable();
            $table->timestamps();

            $table->unique(['integration_type', 'provider']);
            $table->index(['integration_type', 'is_active']);
            $table->index(['provider', 'is_active']);
            $table->index('health_status');
            $table->index('last_sync_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_configurations');
    }
};