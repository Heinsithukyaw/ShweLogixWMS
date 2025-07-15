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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event_type');
            $table->string('notification_type'); // 'email', 'sms', 'push', 'in_app'
            $table->string('subject')->nullable();
            $table->text('body_template');
            $table->json('placeholders')->nullable(); // Available placeholders for the template
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add indexes for efficient querying
            $table->index('event_type');
            $table->index('notification_type');
            $table->index('is_active');
            $table->unique(['event_type', 'notification_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};