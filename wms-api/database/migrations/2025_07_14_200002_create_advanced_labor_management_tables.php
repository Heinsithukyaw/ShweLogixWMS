<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Labor Shifts
        Schema::create('labor_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_name');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->boolean('is_overnight')->default(false);
            $table->json('working_days'); // Array of days: ['monday', 'tuesday', ...]
            $table->decimal('base_hourly_rate', 8, 2)->default(0);
            $table->decimal('overtime_multiplier', 3, 2)->default(1.5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Labor Schedules
        Schema::create('labor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('shift_id')->constrained('labor_shifts');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('schedule_date');
            $table->time('scheduled_start');
            $table->time('scheduled_end');
            $table->time('actual_start')->nullable();
            $table->time('actual_end')->nullable();
            $table->enum('status', ['scheduled', 'checked_in', 'on_break', 'checked_out', 'absent', 'late', 'overtime']);
            $table->integer('scheduled_hours');
            $table->integer('actual_hours')->default(0);
            $table->integer('overtime_hours')->default(0);
            $table->integer('break_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees');
            $table->timestamps();
            
            $table->unique(['employee_id', 'schedule_date']);
            $table->index(['warehouse_id', 'schedule_date']);
            $table->index(['shift_id', 'schedule_date']);
        });

        // Labor Time Tracking
        Schema::create('labor_time_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('schedule_id')->constrained('labor_schedules');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->enum('action', ['check_in', 'check_out', 'break_start', 'break_end', 'task_start', 'task_end']);
            $table->datetime('timestamp');
            $table->string('location')->nullable(); // GPS coordinates or zone
            $table->string('device_id')->nullable();
            $table->foreignId('task_id')->nullable(); // Reference to specific task
            $table->string('task_type')->nullable(); // picking, packing, receiving, etc.
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional tracking data
            $table->timestamps();
            
            $table->index(['employee_id', 'timestamp']);
            $table->index(['warehouse_id', 'timestamp']);
        });

        // Labor Tasks
        Schema::create('labor_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_number')->unique();
            $table->enum('task_type', ['picking', 'packing', 'receiving', 'put_away', 'cycle_count', 'loading', 'unloading', 'maintenance', 'cleaning', 'other']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->enum('status', ['pending', 'assigned', 'in_progress', 'completed', 'cancelled', 'on_hold']);
            $table->foreignId('assigned_to')->nullable()->constrained('employees');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('zone_id')->nullable()->constrained('zones');
            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->text('description');
            $table->text('instructions')->nullable();
            $table->integer('estimated_minutes')->default(0);
            $table->integer('actual_minutes')->default(0);
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->decimal('actual_cost', 10, 2)->default(0);
            $table->datetime('scheduled_start')->nullable();
            $table->datetime('scheduled_end')->nullable();
            $table->datetime('actual_start')->nullable();
            $table->datetime('actual_end')->nullable();
            $table->json('required_skills')->nullable();
            $table->json('required_equipment')->nullable();
            $table->text('completion_notes')->nullable();
            $table->decimal('quality_score', 3, 2)->nullable(); // 0-10 scale
            $table->timestamps();
            
            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'scheduled_start']);
            $table->index(['warehouse_id', 'task_type']);
        });

        // Labor Performance Metrics
        Schema::create('labor_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('metric_date');
            $table->string('metric_type'); // productivity, quality, attendance, etc.
            $table->string('task_type')->nullable();
            $table->decimal('target_value', 10, 2)->default(0);
            $table->decimal('actual_value', 10, 2)->default(0);
            $table->decimal('performance_percentage', 5, 2)->default(0);
            $table->integer('tasks_completed')->default(0);
            $table->integer('tasks_assigned')->default(0);
            $table->decimal('average_task_time', 8, 2)->default(0); // minutes
            $table->decimal('quality_score', 3, 2)->default(0);
            $table->integer('errors_count')->default(0);
            $table->integer('hours_worked')->default(0);
            $table->integer('overtime_hours')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['employee_id', 'metric_date', 'metric_type', 'task_type']);
            $table->index(['warehouse_id', 'metric_date']);
        });

        // Labor Skills
        Schema::create('labor_skills', function (Blueprint $table) {
            $table->id();
            $table->string('skill_name');
            $table->text('description')->nullable();
            $table->enum('category', ['equipment', 'software', 'safety', 'quality', 'leadership', 'technical', 'other']);
            $table->enum('level_required', ['beginner', 'intermediate', 'advanced', 'expert']);
            $table->boolean('requires_certification')->default(false);
            $table->integer('training_hours')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Employee Skills
        Schema::create('employee_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('skill_id')->constrained('labor_skills');
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert']);
            $table->date('acquired_date');
            $table->date('last_assessed_date')->nullable();
            $table->date('certification_date')->nullable();
            $table->date('certification_expiry')->nullable();
            $table->string('certification_number')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['employee_id', 'skill_id']);
        });

        // Labor Cost Centers
        Schema::create('labor_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('cost_center_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('manager_id')->nullable()->constrained('employees');
            $table->decimal('hourly_rate_standard', 8, 2)->default(0);
            $table->decimal('hourly_rate_overtime', 8, 2)->default(0);
            $table->decimal('monthly_budget', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Labor Cost Tracking
        Schema::create('labor_cost_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('cost_center_id')->constrained('labor_cost_centers');
            $table->foreignId('task_id')->nullable()->constrained('labor_tasks');
            $table->date('cost_date');
            $table->decimal('regular_hours', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('regular_rate', 8, 2)->default(0);
            $table->decimal('overtime_rate', 8, 2)->default(0);
            $table->decimal('regular_cost', 10, 2)->default(0);
            $table->decimal('overtime_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('benefits_cost', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['cost_center_id', 'cost_date']);
            $table->index(['employee_id', 'cost_date']);
        });

        // Labor Productivity Standards
        Schema::create('labor_productivity_standards', function (Blueprint $table) {
            $table->id();
            $table->string('task_type');
            $table->string('sub_task')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->decimal('standard_time_minutes', 8, 2); // Expected time per unit
            $table->string('unit_of_measure'); // per item, per pallet, per order, etc.
            $table->decimal('target_productivity', 8, 2); // units per hour
            $table->decimal('minimum_acceptable', 8, 2); // minimum units per hour
            $table->decimal('excellent_performance', 8, 2); // excellent units per hour
            $table->json('factors')->nullable(); // Factors affecting productivity
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['task_type', 'warehouse_id', 'is_active']);
        });

        // Labor Training Records
        Schema::create('labor_training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('skill_id')->nullable()->constrained('labor_skills');
            $table->string('training_name');
            $table->text('description')->nullable();
            $table->enum('training_type', ['orientation', 'safety', 'skill_development', 'certification', 'refresher', 'compliance']);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'failed', 'cancelled']);
            $table->foreignId('trainer_id')->nullable()->constrained('users');
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->integer('duration_hours');
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('score', 5, 2)->nullable(); // Training score
            $table->boolean('passed')->nullable();
            $table->text('notes')->nullable();
            $table->json('materials')->nullable(); // Training materials used
            $table->timestamps();
            
            $table->index(['employee_id', 'training_type']);
            $table->index(['scheduled_date', 'status']);
        });

        // Labor Analytics Summary
        Schema::create('labor_analytics_summary', function (Blueprint $table) {
            $table->id();
            $table->date('analytics_date');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('department')->nullable();
            $table->integer('total_employees')->default(0);
            $table->integer('employees_present')->default(0);
            $table->integer('employees_absent')->default(0);
            $table->decimal('attendance_rate', 5, 2)->default(0);
            $table->decimal('total_hours_scheduled', 10, 2)->default(0);
            $table->decimal('total_hours_worked', 10, 2)->default(0);
            $table->decimal('total_overtime_hours', 10, 2)->default(0);
            $table->decimal('productivity_rate', 5, 2)->default(0);
            $table->decimal('total_labor_cost', 15, 2)->default(0);
            $table->decimal('cost_per_hour', 8, 2)->default(0);
            $table->integer('tasks_completed')->default(0);
            $table->integer('tasks_assigned')->default(0);
            $table->decimal('task_completion_rate', 5, 2)->default(0);
            $table->decimal('average_quality_score', 3, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['analytics_date', 'warehouse_id', 'department']);
            $table->index(['analytics_date', 'warehouse_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('labor_analytics_summary');
        Schema::dropIfExists('labor_training_records');
        Schema::dropIfExists('labor_productivity_standards');
        Schema::dropIfExists('labor_cost_tracking');
        Schema::dropIfExists('labor_cost_centers');
        Schema::dropIfExists('employee_skills');
        Schema::dropIfExists('labor_skills');
        Schema::dropIfExists('labor_performance_metrics');
        Schema::dropIfExists('labor_tasks');
        Schema::dropIfExists('labor_time_tracking');
        Schema::dropIfExists('labor_schedules');
        Schema::dropIfExists('labor_shifts');
    }
};