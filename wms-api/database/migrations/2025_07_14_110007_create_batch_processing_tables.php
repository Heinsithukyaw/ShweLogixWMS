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
        // Batch job definitions
        Schema::create('batch_job_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('job_code')->unique();
            $table->text('description')->nullable();
            $table->string('entity_type'); // order, product, inventory, etc.
            $table->string('job_type'); // import, export, process, sync
            $table->json('job_configuration'); // Configuration for the job
            $table->string('processor_class'); // PHP class that processes the job
            $table->integer('chunk_size')->default(100); // Number of records per chunk
            $table->integer('max_retries')->default(3); // Maximum retry attempts
            $table->integer('timeout_minutes')->default(60); // Job timeout
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Batch job schedules
        Schema::create('batch_job_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_definition_id')->constrained('batch_job_definitions');
            $table->string('schedule_type'); // cron, interval, one-time
            $table->string('cron_expression')->nullable(); // For cron schedules
            $table->integer('interval_minutes')->nullable(); // For interval schedules
            $table->timestamp('next_run_time')->nullable();
            $table->timestamp('last_run_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Batch job instances
        Schema::create('batch_job_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_definition_id')->constrained('batch_job_definitions');
            $table->foreignId('schedule_id')->nullable()->constrained('batch_job_schedules');
            $table->string('status'); // queued, running, completed, failed, cancelled
            $table->json('job_parameters')->nullable(); // Parameters for this instance
            $table->integer('total_records')->nullable(); // Total records to process
            $table->integer('processed_records')->default(0); // Records processed
            $table->integer('success_records')->default(0); // Records successfully processed
            $table->integer('error_records')->default(0); // Records with errors
            $table->integer('retry_count')->default(0); // Current retry attempt
            $table->text('input_file_path')->nullable(); // For import jobs
            $table->text('output_file_path')->nullable(); // For export jobs
            $table->text('error_file_path')->nullable(); // For error records
            $table->text('error_message')->nullable(); // Overall error message
            $table->foreignId('initiated_by')->nullable()->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index(['job_definition_id', 'status']);
        });
        
        // Batch job chunks
        Schema::create('batch_job_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_instance_id')->constrained('batch_job_instances');
            $table->integer('chunk_number');
            $table->string('status'); // queued, running, completed, failed
            $table->integer('total_records'); // Records in this chunk
            $table->integer('processed_records')->default(0);
            $table->integer('success_records')->default(0);
            $table->integer('error_records')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['job_instance_id', 'chunk_number']);
            $table->index('status');
        });
        
        // Batch job records
        Schema::create('batch_job_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_instance_id')->constrained('batch_job_instances');
            $table->foreignId('chunk_id')->nullable()->constrained('batch_job_chunks');
            $table->integer('record_number');
            $table->string('status'); // pending, processed, error
            $table->string('entity_type')->nullable(); // Related entity type
            $table->string('entity_id')->nullable(); // Related entity ID
            $table->json('record_data')->nullable(); // Original record data
            $table->json('processed_data')->nullable(); // Processed record data
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['job_instance_id', 'record_number']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('status');
        });
        
        // Enhanced file transfers
        Schema::create('file_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('transfer_type'); // ftp, sftp, s3, azure, local
            $table->string('direction'); // upload, download
            $table->json('connection_details'); // Connection parameters
            $table->string('source_path');
            $table->string('destination_path');
            $table->string('file_pattern')->nullable(); // For wildcard matching
            $table->boolean('delete_source')->default(false);
            $table->boolean('overwrite_destination')->default(false);
            $table->boolean('encrypt_file')->default(false);
            $table->string('encryption_method')->nullable(); // AES, PGP, etc.
            $table->text('encryption_key_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // File transfer logs
        Schema::create('file_transfer_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_transfer_id')->constrained('file_transfers');
            $table->string('status'); // queued, running, completed, failed
            $table->string('file_name');
            $table->bigInteger('file_size')->nullable();
            $table->string('source_path');
            $table->string('destination_path');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['file_transfer_id', 'status']);
            $table->index('file_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_transfer_logs');
        Schema::dropIfExists('file_transfers');
        Schema::dropIfExists('batch_job_records');
        Schema::dropIfExists('batch_job_chunks');
        Schema::dropIfExists('batch_job_instances');
        Schema::dropIfExists('batch_job_schedules');
        Schema::dropIfExists('batch_job_definitions');
    }
};