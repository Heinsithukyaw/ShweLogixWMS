<?php

namespace App\Models\Visualization;

use App\Models\SpaceUtilization\WarehouseZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseEquipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'status',
        'current_x',
        'current_y',
        'current_z',
        'current_zone_id',
        'specifications',
        'last_activity',
        'battery_level',
        'sensor_data'
    ];

    protected $casts = [
        'current_x' => 'decimal:2',
        'current_y' => 'decimal:2',
        'current_z' => 'decimal:2',
        'specifications' => 'array',
        'last_activity' => 'datetime',
        'battery_level' => 'decimal:2',
        'sensor_data' => 'array'
    ];

    // Relationships
    public function currentZone()
    {
        return $this->belongsTo(WarehouseZone::class, 'current_zone_id');
    }

    public function movements()
    {
        return $this->hasMany(EquipmentMovement::class, 'equipment_id');
    }

    public function alerts()
    {
        return $this->hasMany(VisualizationAlert::class, 'equipment_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInZone($query, $zoneId)
    {
        return $query->where('current_zone_id', $zoneId);
    }

    public function scopeLowBattery($query, $threshold = 20)
    {
        return $query->where('battery_level', '<', $threshold);
    }

    // Methods
    public function updatePosition($x, $y, $z = null, $zoneId = null)
    {
        $oldPosition = [
            'x' => $this->current_x,
            'y' => $this->current_y,
            'z' => $this->current_z,
            'zone_id' => $this->current_zone_id
        ];

        $this->current_x = $x;
        $this->current_y = $y;
        if ($z !== null) $this->current_z = $z;
        if ($zoneId !== null) $this->current_zone_id = $zoneId;
        $this->last_activity = now();
        $this->save();

        // Record movement
        $this->recordMovement($oldPosition, [
            'x' => $x,
            'y' => $y,
            'z' => $z ?? $this->current_z,
            'zone_id' => $zoneId ?? $this->current_zone_id
        ]);
    }

    public function recordMovement($from, $to)
    {
        $distance = $this->calculateDistance($from, $to);
        
        return $this->movements()->create([
            'movement_time' => now(),
            'from_x' => $from['x'],
            'from_y' => $from['y'],
            'to_x' => $to['x'],
            'to_y' => $to['y'],
            'from_zone_id' => $from['zone_id'],
            'to_zone_id' => $to['zone_id'],
            'distance_traveled' => $distance,
            'duration_seconds' => 0, // Will be updated when movement completes
            'movement_type' => 'task'
        ]);
    }

    public function calculateDistance($from, $to)
    {
        return sqrt(
            pow($to['x'] - $from['x'], 2) + 
            pow($to['y'] - $from['y'], 2)
        );
    }

    public function getMovementHistory($days = 7)
    {
        return $this->movements()
            ->where('movement_time', '>=', now()->subDays($days))
            ->orderBy('movement_time', 'desc')
            ->get();
    }

    public function getTotalDistanceTraveled($days = 1)
    {
        return $this->movements()
            ->where('movement_time', '>=', now()->subDays($days))
            ->sum('distance_traveled');
    }

    public function getAverageSpeed($days = 1)
    {
        $movements = $this->movements()
            ->where('movement_time', '>=', now()->subDays($days))
            ->where('duration_seconds', '>', 0)
            ->get();

        if ($movements->isEmpty()) {
            return 0;
        }

        $totalDistance = $movements->sum('distance_traveled');
        $totalTime = $movements->sum('duration_seconds');

        return $totalTime > 0 ? $totalDistance / $totalTime : 0;
    }

    public function getUtilizationRate($days = 1)
    {
        $totalTime = $days * 24 * 60 * 60; // Total seconds in the period
        $activeTime = $this->movements()
            ->where('movement_time', '>=', now()->subDays($days))
            ->sum('duration_seconds');

        return ($activeTime / $totalTime) * 100;
    }

    public function getBatteryStatus()
    {
        if ($this->battery_level === null) {
            return 'not_applicable';
        }

        if ($this->battery_level < 10) {
            return 'critical';
        } elseif ($this->battery_level < 25) {
            return 'low';
        } elseif ($this->battery_level < 50) {
            return 'medium';
        } else {
            return 'good';
        }
    }

    public function needsMaintenance()
    {
        $lastMaintenance = $this->specifications['last_maintenance'] ?? null;
        $maintenanceInterval = $this->specifications['maintenance_interval_days'] ?? 30;

        if (!$lastMaintenance) {
            return true;
        }

        $lastMaintenanceDate = \Carbon\Carbon::parse($lastMaintenance);
        return $lastMaintenanceDate->addDays($maintenanceInterval)->isPast();
    }

    public function getMaintenanceDue()
    {
        $lastMaintenance = $this->specifications['last_maintenance'] ?? null;
        $maintenanceInterval = $this->specifications['maintenance_interval_days'] ?? 30;

        if (!$lastMaintenance) {
            return now();
        }

        return \Carbon\Carbon::parse($lastMaintenance)->addDays($maintenanceInterval);
    }

    public function updateSensorData($sensorData)
    {
        $this->sensor_data = array_merge($this->sensor_data ?? [], $sensorData);
        $this->last_activity = now();
        $this->save();
    }

    public function isOnline()
    {
        return $this->last_activity && $this->last_activity->diffInMinutes(now()) < 5;
    }
}