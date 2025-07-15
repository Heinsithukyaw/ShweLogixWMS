<?php

namespace App\Models\Batch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchJobRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_instance_id',
        'chunk_id',
        'record_index',
        'status',
        'record_data',
        'result_data',
        'error_message',
        'retry_count',
        'processed_at',
    ];

    protected $casts = [
        'record_index' => 'integer',
        'record_data' => 'json',
        'result_data' => 'json',
        'retry_count' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function jobInstance()
    {
        return $this->belongsTo(BatchJobInstance::class, 'job_instance_id');
    }

    public function chunk()
    {
        return $this->belongsTo(BatchJobChunk::class, 'chunk_id');
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    public function isSuccess()
    {
        return $this->status === 'success';
    }

    public function isError()
    {
        return $this->status === 'error';
    }

    public function isSkipped()
    {
        return $this->status === 'skipped';
    }
}