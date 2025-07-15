<?php

namespace App\Models\Batch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatchJobDefinition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'job_code',
        'description',
        'entity_type',
        'job_type',
        'job_configuration',
        'processor_class',
        'chunk_size',
        'max_retries',
        'timeout_minutes',
        'is_active',
    ];

    protected $casts = [
        'job_configuration' => 'json',
        'chunk_size' => 'integer',
        'max_retries' => 'integer',
        'timeout_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function schedules()
    {
        return $this->hasMany(BatchJobSchedule::class, 'job_definition_id');
    }

    public function instances()
    {
        return $this->hasMany(BatchJobInstance::class, 'job_definition_id');
    }

    public function activeSchedules()
    {
        return $this->schedules()->where('is_active', true);
    }

    public function recentInstances($limit = 10)
    {
        return $this->instances()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getProcessorClass()
    {
        return $this->processor_class;
    }

    public function isImportJob()
    {
        return $this->job_type === 'import';
    }

    public function isExportJob()
    {
        return $this->job_type === 'export';
    }

    public function isProcessJob()
    {
        return $this->job_type === 'process';
    }

    public function isSyncJob()
    {
        return $this->job_type === 'sync';
    }
}