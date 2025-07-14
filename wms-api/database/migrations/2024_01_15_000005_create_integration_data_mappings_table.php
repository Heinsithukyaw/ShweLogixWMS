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
        Schema::create('integration_data_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('integration_type');
            $table->string('provider');
            $table->string('data_type'); // product, order, customer, etc.
            $table->string('direction'); // inbound, outbound
            $table->string('source_field');
            $table->string('target_field');
            $table->string('field_type')->nullable(); // string, number, boolean, date, array, object
            $table->boolean('is_required')->default(false);
            $table->string('default_value')->nullable();
            $table->json('transformation_rules')->nullable(); // Custom transformation logic
            $table->json('validation_rules')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['integration_type', 'provider', 'data_type', 'direction', 'source_field'], 'unique_mapping');
            $table->index(['integration_type', 'provider', 'data_type'], 'idx_integration_mapping');
            $table->index(['direction', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_data_mappings');
    }
};