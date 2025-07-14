<?php

namespace App\Models\EDI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EdiAcknowledgment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'acknowledgment_type',
        'status',
        'original_file_path',
        'acknowledgment_data',
        'sent_at',
        'received_at',
    ];

    protected $casts = [
        'acknowledgment_data' => 'json',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(EdiTransaction::class, 'transaction_id');
    }

    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isPartial()
    {
        return $this->status === 'partial';
    }

    public function getOriginalFileContents()
    {
        if (!$this->original_file_path || !file_exists($this->original_file_path)) {
            return null;
        }
        
        return file_get_contents($this->original_file_path);
    }
}