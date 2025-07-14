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
        Schema::create('optimization_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('warehouse_layout_id')->nullable()->constrained('warehouse_layouts')->onDelete('set null');
            $table->string('metric_type'); // space_utilization, travel_distance, throughput, picking_efficiency
            $table->decimal('value', 10, 2);
            $table->json('details')->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('optimization_metrics');
    }
};