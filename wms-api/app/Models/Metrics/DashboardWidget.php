<?php

namespace App\Models\Metrics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    use HasFactory;

    protected $fillable = [
        'dashboard_id',
        'metric_visualization_id',
        'position_x',
        'position_y',
        'width',
        'height',
        'is_active',
    ];

    protected $casts = [
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the dashboard that owns this widget.
     */
    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * Get the metric visualization that owns this widget.
     */
    public function metricVisualization()
    {
        return $this->belongsTo(MetricVisualization::class);
    }
}