<?php

namespace App\Models\Batch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BatchJobInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_definition_id',
        'schedule_id',
        'status',
        'job_parameters',
        'total_records',
        'processed_records',
        'success_records',
        'error_records',
        'retry_count',
        'input_file_path',
        'output_file_path',
        'error_file_path',
        'error_message',
        'initiated_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'job_parameters' => 'json',
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'success_records' => 'integer',
        'error_records' => 'integer',
        'retry_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function jobDefinition()
    {
        return $this->belongsTo(BatchJobDefinition::class, 'job_definition_id');
    }

    public function schedule()
    {
        return $this->belongsTo(BatchJobSchedule::class, 'schedule_id');
    }

    public function chunks()
    {
        return $this->hasMany(BatchJobChunk::class, 'job_instance_id');
    }

    public function records()
    {
        return $this->hasMany(BatchJobRecord::class, 'job_instance_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
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

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function getProgressPercentage()
    {
        if (!$this->total_records || $this->total_records <= 0) {
            return 0;
        }
        
        return min(100, round(($this->processed_records / $this->total_records) * 100, 2));
    }

    public function getSuccessRate()
    {
        if (!$this->processed_records || $this->processed_records <= 0) {
            return 0;
        }
        
        return round(($this->success_records / $this->processed_records) * 100, 2);
    }

    public function getErrorRate()
    {
        if (!$this->processed_records || $this->processed_records <= 0) {
            return 0;
        }
        
        return round(($this->error_records / $this->processed_records) * 100, 2);
    }

    public function getDurationInSeconds()
    {
        if (!$this->started_at) {
            return 0;
        }
        
        $endTime = $this->completed_at ?? now();
        return $endTime->diffInSeconds($this->started_at);
    }

    public function getFormattedDuration()
    {
        $seconds = $this->getDurationInSeconds();
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}