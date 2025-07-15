<?php

namespace App\Models\Batch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchJobSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_definition_id',
        'schedule_type',
        'cron_expression',
        'interval_minutes',
        'next_run_time',
        'last_run_time',
        'is_active',
    ];

    protected $casts = [
        'interval_minutes' => 'integer',
        'next_run_time' => 'datetime',
        'last_run_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function jobDefinition()
    {
        return $this->belongsTo(BatchJobDefinition::class, 'job_definition_id');
    }

    public function instances()
    {
        return $this->hasMany(BatchJobInstance::class, 'schedule_id');
    }

    public function isCronSchedule()
    {
        return $this->schedule_type === 'cron';
    }

    public function isIntervalSchedule()
    {
        return $this->schedule_type === 'interval';
    }

    public function isOneTimeSchedule()
    {
        return $this->schedule_type === 'one-time';
    }

    public function calculateNextRunTime()
    {
        if (!$this->is_active) {
            return null;
        }
        
        if ($this->isOneTimeSchedule()) {
            // One-time schedules don't have a next run time after they've run
            if ($this->last_run_time) {
                return null;
            }
            
            return $this->next_run_time;
        }
        
        if ($this->isIntervalSchedule()) {
            $lastRun = $this->last_run_time ?? now();
            return $lastRun->addMinutes($this->interval_minutes);
        }
        
        if ($this->isCronSchedule() && $this->cron_expression) {
            // Use a cron parser library to calculate the next run time
            // This is a simplified example
            $cron = new \Cron\CronExpression($this->cron_expression);
            return $cron->getNextRunDate();
        }
        
        return null;
    }

    public function updateNextRunTime()
    {
        $this->next_run_time = $this->calculateNextRunTime();
        $this->save();
        
        return $this->next_run_time;
    }
}