<?php

namespace App\Models\Deduplication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DeduplicationExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'execution_id',
        'status',
        'total_records',
        'processed_records',
        'duplicate_records',
        'merged_records',
        'execution_parameters',
        'error_message',
        'initiated_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'duplicate_records' => 'integer',
        'merged_records' => 'integer',
        'execution_parameters' => 'json',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function rule()
    {
        return $this->belongsTo(DeduplicationRule::class, 'rule_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function matches()
    {
        return $this->hasMany(DuplicateMatch::class, 'execution_id');
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

    public function getDuplicateRate()
    {
        if (!$this->processed_records || $this->processed_records <= 0) {
            return 0;
        }
        
        return round(($this->duplicate_records / $this->processed_records) * 100, 2);
    }

    public function getMergeRate()
    {
        if (!$this->duplicate_records || $this->duplicate_records <= 0) {
            return 0;
        }
        
        return round(($this->merged_records / $this->duplicate_records) * 100, 2);
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