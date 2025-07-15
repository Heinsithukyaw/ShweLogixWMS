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
        // Report templates
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('category'); // financial, operational, inventory, performance
            $table->text('description')->nullable();
            $table->json('data_sources'); // tables/models to query
            $table->json('fields_config'); // field definitions and formatting
            $table->json('filters_config')->nullable(); // available filters
            $table->json('grouping_config')->nullable(); // grouping options
            $table->json('sorting_config')->nullable(); // default sorting
            $table->json('chart_config')->nullable(); // chart/visualization settings
            $table->json('layout_config'); // report layout and styling
            $table->string('output_formats')->default('pdf,excel,csv'); // supported formats
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Custom reports built from templates
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('report_templates')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('filter_values')->nullable(); // applied filter values
            $table->json('field_selections'); // selected fields
            $table->json('grouping_selections')->nullable(); // applied grouping
            $table->json('sorting_selections')->nullable(); // applied sorting
            $table->json('chart_selections')->nullable(); // chart customizations
            $table->json('layout_customizations')->nullable(); // layout overrides
            $table->string('output_format')->default('pdf');
            $table->boolean('is_favorite')->default(false);
            $table->string('created_by');
            $table->string('shared_with')->nullable(); // user IDs or roles
            $table->timestamps();
            $table->softDeletes();
        });

        // Scheduled reports
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_report_id')->constrained('custom_reports')->onDelete('cascade');
            $table->string('name');
            $table->string('schedule_type'); // daily, weekly, monthly, quarterly, yearly
            $table->json('schedule_config'); // cron expression, specific days, etc.
            $table->time('execution_time');
            $table->string('timezone')->default('UTC');
            $table->json('recipients'); // email addresses
            $table->string('delivery_method')->default('email'); // email, ftp, api
            $table->json('delivery_config')->nullable(); // delivery settings
            $table->datetime('next_execution');
            $table->datetime('last_execution')->nullable();
            $table->string('status')->default('active'); // active, paused, failed
            $table->integer('execution_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->text('last_error')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

        // Report execution history
        Schema::create('report_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_report_id')->nullable()->constrained('custom_reports')->onDelete('set null');
            $table->foreignId('scheduled_report_id')->nullable()->constrained('scheduled_reports')->onDelete('set null');
            $table->string('execution_type'); // manual, scheduled
            $table->datetime('started_at');
            $table->datetime('completed_at')->nullable();
            $table->string('status'); // running, completed, failed, cancelled
            $table->integer('total_records')->nullable();
            $table->decimal('execution_time_seconds', 8, 2)->nullable();
            $table->string('output_format');
            $table->string('file_path')->nullable(); // generated file location
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->text('error_message')->nullable();
            $table->json('execution_parameters')->nullable(); // filters, etc.
            $table->string('executed_by')->nullable();
            $table->timestamps();
            
            $table->index(['custom_report_id', 'started_at']);
            $table->index(['scheduled_report_id', 'started_at']);
        });

        // Report data cache for performance
        Schema::create('report_data_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique();
            $table->foreignId('template_id')->constrained('report_templates')->onDelete('cascade');
            $table->json('filter_hash'); // hash of filter parameters
            $table->longText('cached_data'); // serialized result data
            $table->integer('record_count');
            $table->datetime('generated_at');
            $table->datetime('expires_at');
            $table->boolean('is_valid')->default(true);
            $table->timestamps();
            
            $table->index(['template_id', 'expires_at']);
        });

        // Report builder configurations
        Schema::create('report_builder_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_type'); // data_source, field_type, filter_type, chart_type
            $table->string('name');
            $table->string('code')->unique();
            $table->json('configuration'); // specific config data
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Export jobs queue
        Schema::create('report_export_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_execution_id')->constrained('report_executions')->onDelete('cascade');
            $table->string('export_format'); // pdf, excel, csv
            $table->string('status')->default('queued'); // queued, processing, completed, failed
            $table->datetime('queued_at');
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->string('file_path')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->json('export_options')->nullable(); // format-specific options
            $table->string('requested_by');
            $table->timestamps();
            
            $table->index(['status', 'queued_at']);
        });

        // Report permissions
        Schema::create('report_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->nullable()->constrained('report_templates')->onDelete('cascade');
            $table->foreignId('custom_report_id')->nullable()->constrained('custom_reports')->onDelete('cascade');
            $table->string('user_id')->nullable(); // specific user
            $table->string('role')->nullable(); // user role
            $table->string('permission_type'); // view, edit, delete, schedule, share
            $table->boolean('is_granted')->default(true);
            $table->string('granted_by');
            $table->datetime('granted_at');
            $table->datetime('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['template_id', 'permission_type']);
            $table->index(['custom_report_id', 'permission_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_permissions');
        Schema::dropIfExists('report_export_jobs');
        Schema::dropIfExists('report_builder_configs');
        Schema::dropIfExists('report_data_cache');
        Schema::dropIfExists('report_executions');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('custom_reports');
        Schema::dropIfExists('report_templates');
    }
};
