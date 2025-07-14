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
        // Workflow definitions
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('entity_type'); // order, receipt, product, etc.
            $table->json('workflow_schema'); // JSON schema defining the workflow
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Workflow steps
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions');
            $table->string('step_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('step_type'); // manual, automatic, approval, notification
            $table->json('step_configuration'); // JSON configuration for the step
            $table->json('transition_rules')->nullable(); // Rules for transitioning to next steps
            $table->boolean('is_start_step')->default(false);
            $table->boolean('is_end_step')->default(false);
            $table->integer('timeout_minutes')->nullable(); // Timeout for the step
            $table->string('timeout_action')->nullable(); // Action to take on timeout
            $table->timestamps();
        });
        
        // Workflow instances
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions');
            $table->string('entity_type');
            $table->string('entity_id');
            $table->string('current_step_code')->nullable();
            $table->string('status'); // active, completed, cancelled, error
            $table->foreignId('initiated_by')->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('workflow_data')->nullable(); // Data collected during workflow
            $table->timestamps();
            
            // Indexes
            $table->index(['entity_type', 'entity_id']);
            $table->index('status');
        });
        
        // Workflow step instances
        Schema::create('workflow_step_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances');
            $table->foreignId('workflow_step_id')->constrained('workflow_steps');
            $table->string('status'); // pending, in_progress, completed, skipped, error
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->json('step_data')->nullable(); // Data collected during this step
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('assigned_to');
        });
        
        // Workflow transitions
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances');
            $table->string('from_step_code');
            $table->string('to_step_code');
            $table->string('transition_type'); // normal, skip, rollback, error
            $table->foreignId('triggered_by')->constrained('users');
            $table->text('transition_reason')->nullable();
            $table->json('transition_data')->nullable();
            $table->timestamps();
        });
        
        // Workflow approvals
        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_step_instance_id')->constrained('workflow_step_instances');
            $table->string('approval_type'); // individual, group, hierarchical
            $table->foreignId('approver_id')->nullable()->constrained('users');
            $table->string('approver_role')->nullable();
            $table->string('status'); // pending, approved, rejected
            $table->text('comments')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('approver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_transitions');
        Schema::dropIfExists('workflow_step_instances');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_definitions');
    }
};