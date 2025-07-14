<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Equipment Categories
        Schema::create('equipment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->text('description')->nullable();
            $table->string('category_code')->unique();
            $table->boolean('requires_certification')->default(false);
            $table->boolean('requires_inspection')->default(false);
            $table->integer('default_inspection_interval_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Equipment Registry
        Schema::create('equipment_registry', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('equipment_categories');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('current_location_id')->nullable()->constrained('locations');
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->decimal('purchase_cost', 15, 2)->default(0);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->enum('status', ['active', 'maintenance', 'repair', 'retired', 'disposed', 'lost']);
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor', 'critical']);
            $table->json('specifications')->nullable(); // Technical specifications
            $table->json('attachments')->nullable(); // Manuals, photos, etc.
            $table->text('notes')->nullable();
            $table->boolean('is_mobile')->default(true);
            $table->boolean('requires_operator')->default(false);
            $table->foreignId('assigned_operator')->nullable()->constrained('employees');
            $table->timestamps();
            
            $table->index(['warehouse_id', 'status']);
            $table->index(['category_id', 'status']);
        });

        // Equipment Maintenance Schedules
        Schema::create('equipment_maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment_registry');
            $table->string('maintenance_type'); // preventive, corrective, predictive
            $table->string('task_name');
            $table->text('description');
            $table->enum('frequency_type', ['daily', 'weekly', 'monthly', 'quarterly', 'annually', 'hours_based', 'usage_based']);
            $table->integer('frequency_value')->default(1); // Every X days/weeks/months
            $table->integer('hours_interval')->nullable(); // For hours-based maintenance
            $table->integer('usage_interval')->nullable(); // For usage-based maintenance
            $table->integer('estimated_duration_minutes')->default(60);
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->json('required_skills')->nullable();
            $table->json('required_parts')->nullable();
            $table->json('required_tools')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('next_due_date')->nullable();
            $table->timestamps();
            
            $table->index(['equipment_id', 'next_due_date']);
            $table->index(['frequency_type', 'is_active']);
        });

        // Equipment Maintenance Records
        Schema::create('equipment_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->string('maintenance_number')->unique();
            $table->foreignId('equipment_id')->constrained('equipment_registry');
            $table->foreignId('schedule_id')->nullable()->constrained('equipment_maintenance_schedules');
            $table->enum('maintenance_type', ['preventive', 'corrective', 'emergency', 'inspection', 'calibration']);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'deferred']);
            $table->enum('priority', ['low', 'medium', 'high', 'critical']);
            $table->text('description');
            $table->text('work_performed')->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('employees');
            $table->foreignId('supervisor_id')->nullable()->constrained('employees');
            $table->datetime('scheduled_start');
            $table->datetime('scheduled_end');
            $table->datetime('actual_start')->nullable();
            $table->datetime('actual_end')->nullable();
            $table->integer('downtime_minutes')->default(0);
            $table->decimal('labor_cost', 10, 2)->default(0);
            $table->decimal('parts_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->json('parts_used')->nullable();
            $table->json('tools_used')->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->boolean('equipment_operational')->default(true);
            $table->date('next_maintenance_due')->nullable();
            $table->timestamps();
            
            $table->index(['equipment_id', 'scheduled_start']);
            $table->index(['status', 'priority']);
            $table->index(['technician_id', 'scheduled_start']);
        });

        // Equipment Utilization Tracking
        Schema::create('equipment_utilization_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment_registry');
            $table->foreignId('operator_id')->nullable()->constrained('employees');
            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->datetime('start_time');
            $table->datetime('end_time')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->enum('usage_type', ['operation', 'idle', 'maintenance', 'transport', 'setup']);
            $table->string('task_reference')->nullable(); // Reference to specific task
            $table->decimal('fuel_consumed', 8, 2)->default(0);
            $table->decimal('distance_traveled', 8, 2)->default(0);
            $table->integer('cycles_completed')->default(0);
            $table->text('notes')->nullable();
            $table->json('performance_metrics')->nullable(); // Speed, efficiency, etc.
            $table->timestamps();
            
            $table->index(['equipment_id', 'start_time']);
            $table->index(['operator_id', 'start_time']);
        });

        // Equipment Performance Metrics
        Schema::create('equipment_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment_registry');
            $table->date('metric_date');
            $table->decimal('total_operating_hours', 8, 2)->default(0);
            $table->decimal('productive_hours', 8, 2)->default(0);
            $table->decimal('idle_hours', 8, 2)->default(0);
            $table->decimal('maintenance_hours', 8, 2)->default(0);
            $table->decimal('utilization_rate', 5, 2)->default(0); // Percentage
            $table->decimal('efficiency_rate', 5, 2)->default(0); // Percentage
            $table->decimal('availability_rate', 5, 2)->default(0); // Percentage
            $table->integer('breakdown_count')->default(0);
            $table->integer('maintenance_count')->default(0);
            $table->decimal('fuel_consumption', 10, 2)->default(0);
            $table->decimal('operating_cost', 10, 2)->default(0);
            $table->decimal('maintenance_cost', 10, 2)->default(0);
            $table->decimal('cost_per_hour', 8, 2)->default(0);
            $table->integer('cycles_completed')->default(0);
            $table->decimal('throughput', 10, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['equipment_id', 'metric_date']);
            $table->index(['metric_date', 'utilization_rate']);
        });

        // Equipment Inspections
        Schema::create('equipment_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_number')->unique();
            $table->foreignId('equipment_id')->constrained('equipment_registry');
            $table->enum('inspection_type', ['daily', 'weekly', 'monthly', 'annual', 'pre_use', 'post_use', 'safety', 'regulatory']);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'failed', 'cancelled']);
            $table->foreignId('inspector_id')->constrained('employees');
            $table->datetime('scheduled_date');
            $table->datetime('completed_date')->nullable();
            $table->json('checklist_items'); // Inspection checklist
            $table->json('results'); // Results for each checklist item
            $table->enum('overall_result', ['pass', 'fail', 'conditional']);
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('photos')->nullable(); // Inspection photos
            $table->boolean('requires_maintenance')->default(false);
            $table->boolean('safe_to_operate')->default(true);
            $table->date('next_inspection_due')->nullable();
            $table->timestamps();
            
            $table->index(['equipment_id', 'scheduled_date']);
            $table->index(['inspector_id', 'scheduled_date']);
            $table->index(['status', 'overall_result']);
        });

        // Equipment Alerts
        Schema::create('equipment_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment_registry');
            $table->enum('alert_type', ['maintenance_due', 'inspection_due', 'breakdown', 'low_fuel', 'overdue', 'safety', 'performance']);
            $table->enum('severity', ['info', 'warning', 'critical', 'emergency']);
            $table->enum('status', ['active', 'acknowledged', 'resolved', 'dismissed']);
            $table->string('title');
            $table->text('message');
            $table->datetime('triggered_at');
            $table->datetime('acknowledged_at')->nullable();
            $table->datetime('resolved_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users');
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->text('resolution_notes')->nullable();
            $table->json('alert_data')->nullable(); // Additional alert context
            $table->timestamps();
            
            $table->index(['equipment_id', 'status']);
            $table->index(['alert_type', 'severity']);
            $table->index(['triggered_at', 'status']);
        });

        // Equipment Lifecycle Events
        Schema::create('equipment_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment_registry');
            $table->enum('event_type', ['purchased', 'deployed', 'relocated', 'assigned', 'unassigned', 'maintenance', 'repair', 'upgrade', 'retired', 'disposed']);
            $table->datetime('event_date');
            $table->foreignId('performed_by')->constrained('users');
            $table->text('description');
            $table->json('event_data')->nullable(); // Additional event context
            $table->decimal('cost', 15, 2)->default(0);
            $table->foreignId('from_location_id')->nullable()->constrained('locations');
            $table->foreignId('to_location_id')->nullable()->constrained('locations');
            $table->foreignId('from_operator_id')->nullable()->constrained('employees');
            $table->foreignId('to_operator_id')->nullable()->constrained('employees');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['equipment_id', 'event_date']);
            $table->index(['event_type', 'event_date']);
        });

        // Equipment Spare Parts
        Schema::create('equipment_spare_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment_registry');
            $table->foreignId('product_id')->constrained('products'); // Reference to inventory product
            $table->string('part_number');
            $table->string('part_name');
            $table->text('description')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->integer('recommended_stock_level')->default(1);
            $table->integer('minimum_stock_level')->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->integer('lead_time_days')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['equipment_id', 'part_number']);
            $table->index(['product_id', 'is_critical']);
        });

        // Equipment Analytics Summary
        Schema::create('equipment_analytics_summary', function (Blueprint $table) {
            $table->id();
            $table->date('analytics_date');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('category_id')->nullable()->constrained('equipment_categories');
            $table->integer('total_equipment')->default(0);
            $table->integer('active_equipment')->default(0);
            $table->integer('maintenance_equipment')->default(0);
            $table->integer('repair_equipment')->default(0);
            $table->decimal('overall_utilization_rate', 5, 2)->default(0);
            $table->decimal('overall_efficiency_rate', 5, 2)->default(0);
            $table->decimal('overall_availability_rate', 5, 2)->default(0);
            $table->decimal('total_operating_hours', 10, 2)->default(0);
            $table->decimal('total_maintenance_cost', 15, 2)->default(0);
            $table->decimal('total_operating_cost', 15, 2)->default(0);
            $table->decimal('cost_per_operating_hour', 8, 2)->default(0);
            $table->integer('maintenance_events')->default(0);
            $table->integer('breakdown_events')->default(0);
            $table->decimal('mean_time_between_failures', 8, 2)->default(0); // Hours
            $table->decimal('mean_time_to_repair', 8, 2)->default(0); // Hours
            $table->timestamps();
            
            $table->unique(['analytics_date', 'warehouse_id', 'category_id']);
            $table->index(['analytics_date', 'warehouse_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('equipment_analytics_summary');
        Schema::dropIfExists('equipment_spare_parts');
        Schema::dropIfExists('equipment_lifecycle_events');
        Schema::dropIfExists('equipment_alerts');
        Schema::dropIfExists('equipment_inspections');
        Schema::dropIfExists('equipment_performance_metrics');
        Schema::dropIfExists('equipment_utilization_tracking');
        Schema::dropIfExists('equipment_maintenance_records');
        Schema::dropIfExists('equipment_maintenance_schedules');
        Schema::dropIfExists('equipment_registry');
        Schema::dropIfExists('equipment_categories');
    }
};