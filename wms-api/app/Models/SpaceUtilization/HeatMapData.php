<?php

namespace App\Models\SpaceUtilization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeatMapData extends Model
{
    use HasFactory;

    protected $fillable = [
        'map_type',
        'zone_id',
        'aisle_id',
        'data_time',
        'x_coordinate',
        'y_coordinate',
        'intensity',
        'intensity_level',
        'metadata'
    ];

    protected $casts = [
        'data_time' => 'datetime',
        'x_coordinate' => 'decimal:2',
        'y_coordinate' => 'decimal:2',
        'intensity' => 'decimal:4',
        'metadata' => 'array'
    ];

    // Relationships
    public function zone()
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }

    public function aisle()
    {
        return $this->belongsTo(WarehouseAisle::class, 'aisle_id');
    }

    // Scopes
    public function scopeByMapType($query, $mapType)
    {
        return $query->where('map_type', $mapType);
    }

    public function scopeForZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    public function scopeForAisle($query, $aisleId)
    {
        return $query->where('aisle_id', $aisleId);
    }

    public function scopeInTimeRange($query, $startTime, $endTime)
    {
        return $query->whereBetween('data_time', [$startTime, $endTime]);
    }

    public function scopeByIntensityLevel($query, $level)
    {
        return $query->where('intensity_level', $level);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('data_time', 'desc');
    }

    // Methods
    public function updateIntensityLevel()
    {
        if ($this->intensity >= 0.8) {
            $this->intensity_level = 'critical';
        } elseif ($this->intensity >= 0.6) {
            $this->intensity_level = 'high';
        } elseif ($this->intensity >= 0.4) {
            $this->intensity_level = 'medium';
        } else {
            $this->intensity_level = 'low';
        }
        
        $this->save();
    }

    public function getColorCode()
    {
        switch ($this->intensity_level) {
            case 'critical':
                return '#FF0000'; // Red
            case 'high':
                return '#FF8000'; // Orange
            case 'medium':
                return '#FFFF00'; // Yellow
            case 'low':
                return '#00FF00'; // Green
            default:
                return '#CCCCCC'; // Gray
        }
    }

    public function getOpacity()
    {
        return min(1.0, max(0.1, $this->intensity));
    }

    public function toHeatMapPoint()
    {
        return [
            'x' => $this->x_coordinate,
            'y' => $this->y_coordinate,
            'intensity' => $this->intensity,
            'level' => $this->intensity_level,
            'color' => $this->getColorCode(),
            'opacity' => $this->getOpacity(),
            'metadata' => $this->metadata
        ];
    }

    public static function generateHeatMapData($mapType, $zoneId = null, $timeRange = null)
    {
        $query = static::byMapType($mapType);
        
        if ($zoneId) {
            $query->forZone($zoneId);
        }
        
        if ($timeRange) {
            $query->inTimeRange($timeRange['start'], $timeRange['end']);
        } else {
            // Default to last 24 hours
            $query->inTimeRange(now()->subDay(), now());
        }
        
        return $query->get()->map(function($point) {
            return $point->toHeatMapPoint();
        });
    }

    public static function getAverageIntensityByZone($mapType, $timeRange = null)
    {
        $query = static::byMapType($mapType);
        
        if ($timeRange) {
            $query->inTimeRange($timeRange['start'], $timeRange['end']);
        }
        
        return $query->selectRaw('zone_id, AVG(intensity) as avg_intensity')
            ->groupBy('zone_id')
            ->with('zone:id,name,code')
            ->get();
    }
}