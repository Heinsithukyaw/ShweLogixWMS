<?php

namespace App\Models\SpaceUtilization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapacityTracking extends Model
{
    use HasFactory;

    protected $table = 'capacity_tracking';

    protected $fillable = [
        'zone_id',
        'tracking_date',
        'max_capacity',
        'current_occupancy',
        'reserved_capacity',
        'available_capacity',
        'capacity_utilization',
        'peak_utilization',
        'peak_time',
        'hourly_utilization',
        'capacity_forecast'
    ];

    protected $casts = [
        'tracking_date' => 'date',
        'capacity_utilization' => 'decimal:2',
        'peak_utilization' => 'decimal:2',
        'peak_time' => 'datetime',
        'hourly_utilization' => 'array',
        'capacity_forecast' => 'array'
    ];

    // Relationships
    public function zone()
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }

    // Scopes
    public function scopeForZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tracking_date', [$startDate, $endDate]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('tracking_date', 'desc');
    }

    // Methods
    public function updateCapacityMetrics()
    {
        $this->available_capacity = $this->max_capacity - $this->current_occupancy - $this->reserved_capacity;
        $this->capacity_utilization = ($this->current_occupancy / $this->max_capacity) * 100;
        $this->save();
    }

    public function getCapacityStatus()
    {
        if ($this->capacity_utilization >= 95) {
            return 'critical';
        } elseif ($this->capacity_utilization >= 85) {
            return 'warning';
        } elseif ($this->capacity_utilization >= 70) {
            return 'good';
        } else {
            return 'low';
        }
    }

    public function getPeakHour()
    {
        if (!$this->hourly_utilization) {
            return null;
        }

        $maxUtilization = 0;
        $peakHour = null;

        foreach ($this->hourly_utilization as $hour => $utilization) {
            if ($utilization > $maxUtilization) {
                $maxUtilization = $utilization;
                $peakHour = $hour;
            }
        }

        return $peakHour;
    }

    public function getAverageHourlyUtilization()
    {
        if (!$this->hourly_utilization) {
            return 0;
        }

        return array_sum($this->hourly_utilization) / count($this->hourly_utilization);
    }

    public function getForecastAccuracy($actualValue)
    {
        if (!$this->capacity_forecast || !isset($this->capacity_forecast['tomorrow'])) {
            return null;
        }

        $forecast = $this->capacity_forecast['tomorrow'];
        $accuracy = 100 - abs(($forecast - $actualValue) / $actualValue * 100);
        
        return max(0, $accuracy);
    }
}