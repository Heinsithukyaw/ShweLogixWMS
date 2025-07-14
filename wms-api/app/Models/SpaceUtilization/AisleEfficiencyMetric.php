<?php

namespace App\Models\SpaceUtilization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AisleEfficiencyMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'aisle_id',
        'metric_date',
        'pick_density',
        'travel_distance',
        'pick_time_avg',
        'congestion_incidents',
        'accessibility_score',
        'efficiency_score',
        'peak_hours',
        'bottleneck_locations'
    ];

    protected $casts = [
        'metric_date' => 'date',
        'pick_density' => 'decimal:2',
        'travel_distance' => 'decimal:2',
        'pick_time_avg' => 'decimal:2',
        'accessibility_score' => 'decimal:2',
        'efficiency_score' => 'decimal:2',
        'peak_hours' => 'array',
        'bottleneck_locations' => 'array'
    ];

    // Relationships
    public function aisle()
    {
        return $this->belongsTo(WarehouseAisle::class, 'aisle_id');
    }

    // Scopes
    public function scopeForAisle($query, $aisleId)
    {
        return $query->where('aisle_id', $aisleId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('metric_date', 'desc');
    }

    // Methods
    public function calculateEfficiencyScore()
    {
        // Weighted calculation of efficiency based on multiple factors
        $pickDensityScore = min(100, ($this->pick_density / 10) * 100); // Assuming 10 picks/meter is optimal
        $travelDistanceScore = max(0, 100 - ($this->travel_distance / 100) * 100); // Lower distance is better
        $pickTimeScore = max(0, 100 - ($this->pick_time_avg / 60) * 100); // Lower time is better
        $congestionScore = max(0, 100 - ($this->congestion_incidents * 10)); // Fewer incidents is better
        
        $this->efficiency_score = (
            $pickDensityScore * 0.3 +
            $travelDistanceScore * 0.25 +
            $pickTimeScore * 0.25 +
            $this->accessibility_score * 0.1 +
            $congestionScore * 0.1
        );
        
        $this->save();
    }

    public function getEfficiencyStatus()
    {
        if ($this->efficiency_score >= 90) {
            return 'excellent';
        } elseif ($this->efficiency_score >= 75) {
            return 'good';
        } elseif ($this->efficiency_score >= 60) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    public function getImprovementSuggestions()
    {
        $suggestions = [];

        if ($this->pick_density < 5) {
            $suggestions[] = 'Consider reorganizing products to increase pick density';
        }

        if ($this->travel_distance > 50) {
            $suggestions[] = 'Optimize pick paths to reduce travel distance';
        }

        if ($this->pick_time_avg > 45) {
            $suggestions[] = 'Review picking procedures to reduce average pick time';
        }

        if ($this->congestion_incidents > 5) {
            $suggestions[] = 'Implement traffic management to reduce congestion';
        }

        if ($this->accessibility_score < 70) {
            $suggestions[] = 'Improve aisle accessibility and organization';
        }

        return $suggestions;
    }

    public function getPeakHoursFormatted()
    {
        if (!$this->peak_hours) {
            return [];
        }

        return array_map(function($hour) {
            return sprintf('%02d:00 - %02d:59', $hour, $hour);
        }, $this->peak_hours);
    }
}