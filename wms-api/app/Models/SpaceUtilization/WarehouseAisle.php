<?php

namespace App\Models\SpaceUtilization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseAisle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'zone_id',
        'name',
        'code',
        'length',
        'width',
        'height',
        'location_count',
        'occupied_locations',
        'utilization_percentage',
        'coordinates',
        'status'
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'utilization_percentage' => 'decimal:2',
        'coordinates' => 'array'
    ];

    // Relationships
    public function zone()
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }

    public function utilizationSnapshots()
    {
        return $this->hasMany(SpaceUtilizationSnapshot::class, 'aisle_id');
    }

    public function efficiencyMetrics()
    {
        return $this->hasMany(AisleEfficiencyMetric::class, 'aisle_id');
    }

    public function heatMapData()
    {
        return $this->hasMany(HeatMapData::class, 'aisle_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    // Methods
    public function updateUtilization()
    {
        if ($this->location_count > 0) {
            $this->utilization_percentage = ($this->occupied_locations / $this->location_count) * 100;
            $this->save();
        }
    }

    public function getAvailableLocations()
    {
        return $this->location_count - $this->occupied_locations;
    }

    public function getEfficiencyTrend($days = 30)
    {
        return $this->efficiencyMetrics()
            ->where('metric_date', '>=', now()->subDays($days))
            ->orderBy('metric_date')
            ->get();
    }

    public function getCurrentEfficiencyScore()
    {
        $latest = $this->efficiencyMetrics()
            ->latest('metric_date')
            ->first();
        
        return $latest ? $latest->efficiency_score : 0;
    }
}