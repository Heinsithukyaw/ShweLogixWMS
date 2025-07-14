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
        // Data sources
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source_type'); // api, database, file, manual, integration
            $table->text('description')->nullable();
            $table->json('connection_details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Data transformations
        Schema::create('data_transformations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('transformation_type'); // etl, calculation, aggregation, mapping
            $table->text('description')->nullable();
            $table->json('transformation_logic')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Data lineage records
        Schema::create('data_lineage_records', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // table name or entity type
            $table->string('entity_id'); // primary key of the entity
            $table->foreignId('source_id')->nullable()->constrained('data_sources');
            $table->foreignId('transformation_id')->nullable()->constrained('data_transformations');
            $table->string('source_entity_type')->nullable();
            $table->string('source_entity_id')->nullable();
            $table->json('lineage_details')->nullable();
            $table->timestamp('processed_at');
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->string('batch_id')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['entity_type', 'entity_id']);
            $table->index(['source_entity_type', 'source_entity_id']);
            $table->index('batch_id');
        });
        
        // Data quality checks
        Schema::create('data_quality_checks', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->string('check_type'); // completeness, accuracy, consistency, validity
            $table->text('check_description');
            $table->json('check_parameters');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Data quality results
        Schema::create('data_quality_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_id')->constrained('data_quality_checks');
            $table->string('entity_type');
            $table->string('entity_id')->nullable();
            $table->boolean('passed');
            $table->text('failure_reason')->nullable();
            $table->json('check_details')->nullable();
            $table->timestamp('checked_at');
            $table->string('batch_id')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['entity_type', 'entity_id']);
            $table->index('batch_id');
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_quality_results');
        Schema::dropIfExists('data_quality_checks');
        Schema::dropIfExists('data_lineage_records');
        Schema::dropIfExists('data_transformations');
        Schema::dropIfExists('data_sources');
    }
};