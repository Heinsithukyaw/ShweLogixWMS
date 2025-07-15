<?php

namespace App\Models\Metrics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetricDefinition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'unit_of_measure',
        'calculation_formula',
        'data_source',
        'frequency',
        'is_kpi',
        'target_value',
        'threshold_warning',
        'threshold_critical',
        'higher_is_better',
        'is_active',
    ];

    protected $casts = [
        'is_kpi' => 'boolean',
        'target_value' => 'decimal:2',
        'threshold_warning' => 'decimal:2',
        'threshold_critical' => 'decimal:2',
        'higher_is_better' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the metric data for this definition.
     */
    public function metricData()
    {
        return $this->hasMany(MetricData::class);
    }

    /**
     * Get the visualizations for this metric definition.
     */
    public function visualizations()
    {
        return $this->hasMany(MetricVisualization::class);
    }
}