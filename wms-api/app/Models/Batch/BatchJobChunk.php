<?php

namespace App\Models\Batch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchJobChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_instance_id',
        'chunk_number',
        'status',
        'total_records',
        'processed_records',
        'success_records',
        'error_records',
        'retry_count',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'chunk_number' => 'integer',
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'success_records' => 'integer',
        'error_records' => 'integer',
        'retry_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function jobInstance()
    {
        return $this->belongsTo(BatchJobInstance::class, 'job_instance_id');
    }

    public function records()
    {
        return $this->hasMany(BatchJobRecord::class, 'chunk_id');
    }

    public function isQueued()
    {
        return $this->status === 'queued';
    }

    public function isRunning()
    {
        return $this->status === 'running';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function getProgressPercentage()
    {
        if (!$this->total_records || $this->total_records <= 0) {
            return 0;
        }
        
        return min(100, round(($this->processed_records / $this->total_records) * 100, 2));
    }

    public function getDurationInSeconds()
    {
        if (!$this->started_at) {
            return 0;
        }
        
        $endTime = $this->completed_at ?? now();
        return $endTime->diffInSeconds($this->started_at);
    }
}