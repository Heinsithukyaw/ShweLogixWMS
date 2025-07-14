<?php

namespace App\Models\EDI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EdiDocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'document_code',
        'description',
        'edi_standard',
        'edi_version',
        'direction',
        'segment_structure',
        'is_active',
    ];

    protected $casts = [
        'segment_structure' => 'json',
        'is_active' => 'boolean',
    ];

    public function mappings()
    {
        return $this->hasMany(EdiMapping::class, 'document_type_id');
    }

    public function transactions()
    {
        return $this->hasMany(EdiTransaction::class, 'document_type_id');
    }

    public function isInbound()
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound()
    {
        return $this->direction === 'outbound';
    }

    public function getStandardAndVersionAttribute()
    {
        return "{$this->edi_standard} {$this->edi_version}";
    }
}