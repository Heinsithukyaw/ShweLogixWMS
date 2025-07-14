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
        Schema::create('notification_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('severity')->nullable(); // 'info', 'warning', 'critical'
            $table->string('source')->nullable(); // Source system or module
            $table->json('conditions')->nullable(); // Additional conditions for routing
            $table->json('recipients'); // User IDs, roles, or other recipient identifiers
            $table->json('notification_channels'); // 'email', 'sms', 'push', 'in_app'
            $table->integer('priority')->default(0); // Higher priority rules are evaluated first
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add indexes for efficient querying
            $table->index('event_type');
            $table->index('severity');
            $table->index('source');
            $table->index('priority');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_routing_rules');
    }
};