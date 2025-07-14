<?php

namespace App\Models\EDI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdocTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'idoc_configuration_id',
        'idoc_number',
        'direction',
        'status',
        'original_file_path',
        'processed_file_path',
        'entity_type',
        'entity_id',
        'transaction_data',
        'error_message',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'transaction_data' => 'json',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function configuration()
    {
        return $this->belongsTo(IdocConfiguration::class, 'idoc_configuration_id');
    }

    public function isInbound()
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound()
    {
        return $this->direction === 'outbound';
    }

    public function isReceived()
    {
        return $this->status === 'received';
    }

    public function isProcessed()
    {
        return $this->status === 'processed';
    }

    public function hasError()
    {
        return $this->status === 'error';
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }

    public function getOriginalFileContents()
    {
        if (!$this->original_file_path || !file_exists($this->original_file_path)) {
            return null;
        }
        
        return file_get_contents($this->original_file_path);
    }

    public function getProcessedFileContents()
    {
        if (!$this->processed_file_path || !file_exists($this->processed_file_path)) {
            return null;
        }
        
        return file_get_contents($this->processed_file_path);
    }
}