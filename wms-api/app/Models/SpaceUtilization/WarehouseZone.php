<?php

namespace App\Models\SpaceUtilization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'length',
        'width',
        'height',
        'total_area',
        'total_volume',
        'usable_area',
        'usable_volume',
        'max_capacity',
        'coordinates',
        'boundaries',
        'status',
        'description'
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'total_area' => 'decimal:2',
        'total_volume' => 'decimal:2',
        'usable_area' => 'decimal:2',
        'usable_volume' => 'decimal:2',
        'coordinates' => 'array',
        'boundaries' => 'array'
    ];

    // Relationships
    public function aisles()
    {
        return $this->hasMany(WarehouseAisle::class, 'zone_id');
    }

    public function utilizationSnapshots()
    {
        return $this->hasMany(SpaceUtilizationSnapshot::class, 'zone_id');
    }

    public function capacityTracking()
    {
        return $this->hasMany(CapacityTracking::class, 'zone_id');
    }

    public function heatMapData()
    {
        return $this->hasMany(HeatMapData::class, 'zone_id');
    }

    public function equipment()
    {
        return $this->hasMany(\App\Models\Visualization\WarehouseEquipment::class, 'current_zone_id');
    }

    // Calculated attributes
    public function getCurrentUtilizationAttribute()
    {
        $latest = $this->utilizationSnapshots()
            ->latest('snapshot_time')
            ->first();
        
        return $latest ? $latest->utilization_percentage : 0;
    }

    public function getAvailableCapacityAttribute()
    {
        $latest = $this->capacityTracking()
            ->latest('tracking_date')
            ->first();
        
        return $latest ? $latest->available_capacity : $this->max_capacity;
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

    // Methods
    public function calculateTotalArea()
    {
        return $this->length * $this->width;
    }

    public function calculateTotalVolume()
    {
        return $this->length * $this->width * $this->height;
    }

    public function updateCalculatedFields()
    {
        $this->total_area = $this->calculateTotalArea();
        $this->total_volume = $this->calculateTotalVolume();
        $this->save();
    }

    public function getUtilizationTrend($days = 30)
    {
        return $this->utilizationSnapshots()
            ->where('snapshot_time', '>=', now()->subDays($days))
            ->orderBy('snapshot_time')
            ->get(['snapshot_time', 'utilization_percentage']);
    }
}