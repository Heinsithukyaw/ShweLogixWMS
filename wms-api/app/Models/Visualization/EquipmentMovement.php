<?php

namespace App\Models\Visualization;

use App\Models\SpaceUtilization\WarehouseZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'movement_time',
        'from_x',
        'from_y',
        'to_x',
        'to_y',
        'from_zone_id',
        'to_zone_id',
        'distance_traveled',
        'duration_seconds',
        'movement_type',
        'path_data'
    ];

    protected $casts = [
        'movement_time' => 'datetime',
        'from_x' => 'decimal:2',
        'from_y' => 'decimal:2',
        'to_x' => 'decimal:2',
        'to_y' => 'decimal:2',
        'distance_traveled' => 'decimal:2',
        'path_data' => 'array'
    ];

    // Relationships
    public function equipment()
    {
        return $this->belongsTo(WarehouseEquipment::class, 'equipment_id');
    }

    public function fromZone()
    {
        return $this->belongsTo(WarehouseZone::class, 'from_zone_id');
    }

    public function toZone()
    {
        return $this->belongsTo(WarehouseZone::class, 'to_zone_id');
    }

    // Scopes
    public function scopeForEquipment($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    public function scopeInTimeRange($query, $startTime, $endTime)
    {
        return $query->whereBetween('movement_time', [$startTime, $endTime]);
    }

    public function scopeByMovementType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeInZone($query, $zoneId)
    {
        return $query->where(function($q) use ($zoneId) {
            $q->where('from_zone_id', $zoneId)
              ->orWhere('to_zone_id', $zoneId);
        });
    }

    // Methods
    public function getSpeed()
    {
        return $this->duration_seconds > 0 ? $this->distance_traveled / $this->duration_seconds : 0;
    }

    public function getDirection()
    {
        $deltaX = $this->to_x - $this->from_x;
        $deltaY = $this->to_y - $this->from_y;
        
        $angle = atan2($deltaY, $deltaX) * 180 / pi();
        
        if ($angle < 0) {
            $angle += 360;
        }
        
        return $angle;
    }

    public function getDirectionLabel()
    {
        $angle = $this->getDirection();
        
        if ($angle >= 337.5 || $angle < 22.5) {
            return 'East';
        } elseif ($angle >= 22.5 && $angle < 67.5) {
            return 'Northeast';
        } elseif ($angle >= 67.5 && $angle < 112.5) {
            return 'North';
        } elseif ($angle >= 112.5 && $angle < 157.5) {
            return 'Northwest';
        } elseif ($angle >= 157.5 && $angle < 202.5) {
            return 'West';
        } elseif ($angle >= 202.5 && $angle < 247.5) {
            return 'Southwest';
        } elseif ($angle >= 247.5 && $angle < 292.5) {
            return 'South';
        } else {
            return 'Southeast';
        }
    }

    public function isInterZoneMovement()
    {
        return $this->from_zone_id !== $this->to_zone_id;
    }

    public function getMovementEfficiency()
    {
        // Calculate efficiency based on straight-line distance vs actual path
        if (!$this->path_data || empty($this->path_data)) {
            return 100; // Assume 100% if no path data
        }
        
        $straightLineDistance = sqrt(
            pow($this->to_x - $this->from_x, 2) + 
            pow($this->to_y - $this->from_y, 2)
        );
        
        if ($straightLineDistance == 0) {
            return 100;
        }
        
        return ($straightLineDistance / $this->distance_traveled) * 100;
    }

    public function addPathPoint($x, $y, $timestamp = null)
    {
        $pathData = $this->path_data ?? [];
        $pathData[] = [
            'x' => $x,
            'y' => $y,
            'timestamp' => $timestamp ?? now()->toISOString()
        ];
        
        $this->path_data = $pathData;
        $this->save();
    }

    public function getPathLength()
    {
        if (!$this->path_data || count($this->path_data) < 2) {
            return $this->distance_traveled;
        }
        
        $totalDistance = 0;
        for ($i = 1; $i < count($this->path_data); $i++) {
            $prev = $this->path_data[$i - 1];
            $curr = $this->path_data[$i];
            
            $distance = sqrt(
                pow($curr['x'] - $prev['x'], 2) + 
                pow($curr['y'] - $prev['y'], 2)
            );
            
            $totalDistance += $distance;
        }
        
        return $totalDistance;
    }

    public function getAverageSpeedAlongPath()
    {
        if (!$this->path_data || count($this->path_data) < 2) {
            return $this->getSpeed();
        }
        
        $pathLength = $this->getPathLength();
        $startTime = \Carbon\Carbon::parse($this->path_data[0]['timestamp']);
        $endTime = \Carbon\Carbon::parse($this->path_data[count($this->path_data) - 1]['timestamp']);
        
        $durationSeconds = $endTime->diffInSeconds($startTime);
        
        return $durationSeconds > 0 ? $pathLength / $durationSeconds : 0;
    }

    public function getStopPoints()
    {
        if (!$this->path_data || count($this->path_data) < 3) {
            return [];
        }
        
        $stopPoints = [];
        $threshold = 0.5; // Minimum distance to consider as movement
        
        for ($i = 1; $i < count($this->path_data) - 1; $i++) {
            $prev = $this->path_data[$i - 1];
            $curr = $this->path_data[$i];
            $next = $this->path_data[$i + 1];
            
            $distToPrev = sqrt(pow($curr['x'] - $prev['x'], 2) + pow($curr['y'] - $prev['y'], 2));
            $distToNext = sqrt(pow($next['x'] - $curr['x'], 2) + pow($next['y'] - $curr['y'], 2));
            
            if ($distToPrev < $threshold && $distToNext < $threshold) {
                $stopPoints[] = $curr;
            }
        }
        
        return $stopPoints;
    }
}