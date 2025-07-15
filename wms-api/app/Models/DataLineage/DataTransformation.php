<?php

namespace App\Models\DataLineage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataTransformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_id',
        'entity_id',
        'field_id',
        'transformation_type',
        'transformation_name',
        'description',
        'transformation_rule',
        'execution_order',
        'is_active',
    ];

    protected $casts = [
        'transformation_rule' => 'json',
        'execution_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function flow()
    {
        return $this->belongsTo(DataFlow::class, 'flow_id');
    }

    public function entity()
    {
        return $this->belongsTo(DataEntity::class, 'entity_id');
    }

    public function field()
    {
        return $this->belongsTo(DataField::class, 'field_id');
    }

    public function isDataTypeConversion()
    {
        return $this->transformation_type === 'data_type_conversion';
    }

    public function isFormatting()
    {
        return $this->transformation_type === 'formatting';
    }

    public function isCalculation()
    {
        return $this->transformation_type === 'calculation';
    }

    public function isLookup()
    {
        return $this->transformation_type === 'lookup';
    }

    public function isAggregation()
    {
        return $this->transformation_type === 'aggregation';
    }

    public function isFiltering()
    {
        return $this->transformation_type === 'filtering';
    }

    public function getTransformationRule()
    {
        return $this->transformation_rule ?? [];
    }

    public function getFormula()
    {
        if (!$this->isCalculation()) {
            return null;
        }
        
        return $this->transformation_rule['formula'] ?? null;
    }

    public function getLookupConfig()
    {
        if (!$this->isLookup()) {
            return null;
        }
        
        return $this->transformation_rule['lookup_config'] ?? null;
    }
}