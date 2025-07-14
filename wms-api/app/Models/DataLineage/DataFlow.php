<?php

namespace App\Models\DataLineage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataFlow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'flow_code',
        'description',
        'source_id',
        'source_entity_id',
        'target_id',
        'target_entity_id',
        'flow_type',
        'flow_configuration',
        'is_active',
    ];

    protected $casts = [
        'flow_configuration' => 'json',
        'is_active' => 'boolean',
    ];

    public function source()
    {
        return $this->belongsTo(DataSource::class, 'source_id');
    }

    public function sourceEntity()
    {
        return $this->belongsTo(DataEntity::class, 'source_entity_id');
    }

    public function target()
    {
        return $this->belongsTo(DataSource::class, 'target_id');
    }

    public function targetEntity()
    {
        return $this->belongsTo(DataEntity::class, 'target_entity_id');
    }

    public function fieldMappings()
    {
        return $this->hasMany(DataFieldMapping::class, 'flow_id');
    }

    public function transformations()
    {
        return $this->hasMany(DataTransformation::class, 'flow_id');
    }

    public function executions()
    {
        return $this->hasMany(DataFlowExecution::class, 'flow_id');
    }

    public function isDirectCopy()
    {
        return $this->flow_type === 'direct_copy';
    }

    public function isTransformation()
    {
        return $this->flow_type === 'transformation';
    }

    public function isAggregation()
    {
        return $this->flow_type === 'aggregation';
    }

    public function isJoin()
    {
        return $this->flow_type === 'join';
    }

    public function getScheduleType()
    {
        return $this->flow_configuration['schedule_type'] ?? 'manual';
    }

    public function getScheduleConfig()
    {
        return $this->flow_configuration['schedule_config'] ?? [];
    }
}