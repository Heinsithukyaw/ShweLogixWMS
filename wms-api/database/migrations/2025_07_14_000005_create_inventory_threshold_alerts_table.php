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
        Schema::create('inventory_threshold_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->string('threshold_type'); // 'low_stock', 'high_stock', 'expiring_soon'
            $table->integer('threshold_value');
            $table->integer('current_value');
            $table->string('severity'); // 'warning', 'critical'
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();
            
            // Add indexes for efficient querying
            $table->index('product_id');
            $table->index('location_id');
            $table->index('threshold_type');
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
        Schema::dropIfExists('inventory_threshold_alerts');
    }
};