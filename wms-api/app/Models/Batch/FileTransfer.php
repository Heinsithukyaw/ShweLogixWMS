<?php

namespace App\Models\Batch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FileTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_configuration_id',
        'schedule_id',
        'status',
        'file_name',
        'file_size',
        'local_path',
        'remote_path',
        'transfer_parameters',
        'error_message',
        'initiated_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'transfer_parameters' => 'json',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function transferConfiguration()
    {
        return $this->belongsTo(FileTransferConfiguration::class, 'transfer_configuration_id');
    }

    public function schedule()
    {
        return $this->belongsTo(FileTransferSchedule::class, 'schedule_id');
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

    public function getFormattedFileSize()
    {
        $bytes = $this->file_size;
        
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
    }
}