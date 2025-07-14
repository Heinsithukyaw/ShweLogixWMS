<?php

namespace App\Models\DataLineage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'entity_code',
        'description',
        'source_id',
        'entity_type',
        'schema_definition',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'schema_definition' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
    ];

    public function source()
    {
        return $this->belongsTo(DataSource::class, 'source_id');
    }

    public function fields()
    {
        return $this->hasMany(DataField::class, 'entity_id');
    }

    public function sourceFlows()
    {
        return $this->hasMany(DataFlow::class, 'source_entity_id');
    }

    public function targetFlows()
    {
        return $this->hasMany(DataFlow::class, 'target_entity_id');
    }

    public function transformations()
    {
        return $this->hasMany(DataTransformation::class, 'entity_id');
    }

    public function isTable()
    {
        return $this->entity_type === 'table';
    }

    public function isView()
    {
        return $this->entity_type === 'view';
    }

    public function isApi()
    {
        return $this->entity_type === 'api';
    }

    public function isFile()
    {
        return $this->entity_type === 'file';
    }

    public function getSchemaFields()
    {
        if (!isset($this->schema_definition['fields'])) {
            return [];
        }
        
        return $this->schema_definition['fields'];
    }

    public function getPrimaryKeys()
    {
        if (!isset($this->schema_definition['primary_keys'])) {
            return [];
        }
        
        return $this->schema_definition['primary_keys'];
    }
}