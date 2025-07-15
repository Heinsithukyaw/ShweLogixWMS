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
        // Deduplication rules
        Schema::create('deduplication_rules', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // product, customer, location, etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('matching_fields'); // Fields to compare
            $table->json('matching_algorithms'); // Algorithms to use for each field
            $table->decimal('threshold', 5, 2); // Matching threshold (0-1)
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_merge')->default(false); // Automatically merge if above threshold
            $table->timestamps();
        });
        
        // Deduplication matches
        Schema::create('deduplication_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('deduplication_rules');
            $table->string('entity_type');
            $table->string('entity_id_1');
            $table->string('entity_id_2');
            $table->decimal('match_score', 5, 2);
            $table->json('field_scores'); // Individual field match scores
            $table->string('status'); // pending, merged, rejected, ignored
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['entity_type', 'entity_id_1', 'entity_id_2']);
            $table->index('status');
        });
        
        // Deduplication merge history
        Schema::create('deduplication_merges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('deduplication_matches');
            $table->string('entity_type');
            $table->string('source_entity_id');
            $table->string('target_entity_id');
            $table->json('merged_fields'); // Fields that were merged
            $table->json('original_values'); // Original values before merge
            $table->foreignId('merged_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index(['entity_type', 'source_entity_id', 'target_entity_id']);
        });
        
        // Fuzzy matching configurations
        Schema::create('fuzzy_matching_configs', function (Blueprint $table) {
            $table->id();
            $table->string('algorithm_name'); // levenshtein, jaro_winkler, soundex, etc.
            $table->string('entity_type');
            $table->string('field_name');
            $table->json('algorithm_parameters'); // Parameters specific to the algorithm
            $table->decimal('weight', 5, 2); // Weight in overall match score
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['algorithm_name', 'entity_type', 'field_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuzzy_matching_configs');
        Schema::dropIfExists('deduplication_merges');
        Schema::dropIfExists('deduplication_matches');
        Schema::dropIfExists('deduplication_rules');
    }
};