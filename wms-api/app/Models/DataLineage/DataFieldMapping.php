<?php

namespace App\Models\DataLineage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataFieldMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_id',
        'source_field_id',
        'target_field_id',
        'transformation_type',
        'transformation_rule',
        'is_active',
    ];

    protected $casts = [
        'transformation_rule' => 'json',
        'is_active' => 'boolean',
    ];

    public function flow()
    {
        return $this->belongsTo(DataFlow::class, 'flow_id');
    }

    public function sourceField()
    {
        return $this->belongsTo(DataField::class, 'source_field_id');
    }

    public function targetField()
    {
        return $this->belongsTo(DataField::class, 'target_field_id');
    }

    public function isDirectMapping()
    {
        return $this->transformation_type === 'direct';
    }

    public function isTransformation()
    {
        return $this->transformation_type === 'transform';
    }

    public function isConstant()
    {
        return $this->transformation_type === 'constant';
    }

    public function isFormula()
    {
        return $this->transformation_type === 'formula';
    }

    public function getTransformationRule()
    {
        return $this->transformation_rule ?? [];
    }

    public function getConstantValue()
    {
        if (!$this->isConstant()) {
            return null;
        }
        
        return $this->transformation_rule['value'] ?? null;
    }

    public function getFormula()
    {
        if (!$this->isFormula()) {
            return null;
        }
        
        return $this->transformation_rule['formula'] ?? null;
    }
}