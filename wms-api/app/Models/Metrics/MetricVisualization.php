<?php

namespace App\Models\Metrics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricVisualization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'configuration',
        'metric_definition_id',
        'time_range',
        'is_active',
    ];

    protected $casts = [
        'configuration' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Get the metric definition that owns this visualization.
     */
    public function metricDefinition()
    {
        return $this->belongsTo(MetricDefinition::class);
    }

    /**
     * Get the dashboard widgets for this visualization.
     */
    public function dashboardWidgets()
    {
        return $this->hasMany(DashboardWidget::class);
    }
}