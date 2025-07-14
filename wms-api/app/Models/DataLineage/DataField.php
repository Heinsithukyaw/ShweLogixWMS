<?php

namespace App\Models\DataLineage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataField extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_id',
        'name',
        'field_code',
        'description',
        'data_type',
        'is_nullable',
        'is_primary_key',
        'is_foreign_key',
        'default_value',
        'field_constraints',
        'metadata',
    ];

    protected $casts = [
        'is_nullable' => 'boolean',
        'is_primary_key' => 'boolean',
        'is_foreign_key' => 'boolean',
        'field_constraints' => 'json',
        'metadata' => 'json',
    ];

    public function entity()
    {
        return $this->belongsTo(DataEntity::class, 'entity_id');
    }

    public function sourceFieldMappings()
    {
        return $this->hasMany(DataFieldMapping::class, 'source_field_id');
    }

    public function targetFieldMappings()
    {
        return $this->hasMany(DataFieldMapping::class, 'target_field_id');
    }

    public function transformations()
    {
        return $this->hasMany(DataTransformation::class, 'field_id');
    }

    public function isNumeric()
    {
        return in_array($this->data_type, ['integer', 'bigint', 'decimal', 'float', 'double']);
    }

    public function isString()
    {
        return in_array($this->data_type, ['char', 'varchar', 'text', 'string']);
    }

    public function isDate()
    {
        return in_array($this->data_type, ['date', 'datetime', 'timestamp']);
    }

    public function isBoolean()
    {
        return in_array($this->data_type, ['boolean', 'bit']);
    }

    public function getConstraints()
    {
        return $this->field_constraints ?? [];
    }

    public function getMaxLength()
    {
        if (!$this->isString()) {
            return null;
        }
        
        return $this->field_constraints['max_length'] ?? null;
    }
}