<?php

namespace App\Models\EDI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EdiMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_partner_id',
        'document_type_id',
        'entity_type',
        'field_mappings',
        'transformation_rules',
        'validation_rules',
        'is_active',
    ];

    protected $casts = [
        'field_mappings' => 'json',
        'transformation_rules' => 'json',
        'validation_rules' => 'json',
        'is_active' => 'boolean',
    ];

    public function tradingPartner()
    {
        return $this->belongsTo(EdiTradingPartner::class, 'trading_partner_id');
    }

    public function documentType()
    {
        return $this->belongsTo(EdiDocumentType::class, 'document_type_id');
    }

    public function getFieldMappingForSegment($segmentName)
    {
        if (!isset($this->field_mappings['segments'])) {
            return null;
        }
        
        foreach ($this->field_mappings['segments'] as $segment) {
            if ($segment['segment_name'] === $segmentName) {
                return $segment;
            }
        }
        
        return null;
    }

    public function getTransformationForField($fieldName)
    {
        if (!isset($this->transformation_rules['fields'])) {
            return null;
        }
        
        foreach ($this->transformation_rules['fields'] as $field) {
            if ($field['field_name'] === $fieldName) {
                return $field['transformations'] ?? null;
            }
        }
        
        return null;
    }

    public function getValidationForField($fieldName)
    {
        if (!isset($this->validation_rules['fields'])) {
            return null;
        }
        
        foreach ($this->validation_rules['fields'] as $field) {
            if ($field['field_name'] === $fieldName) {
                return $field['validations'] ?? null;
            }
        }
        
        return null;
    }
}