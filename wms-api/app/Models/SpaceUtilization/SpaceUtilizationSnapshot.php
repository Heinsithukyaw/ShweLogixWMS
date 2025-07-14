<?php

namespace App\Models\SpaceUtilization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpaceUtilizationSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id',
        'aisle_id',
        'snapshot_time',
        'occupied_area',
        'occupied_volume',
        'occupied_locations',
        'total_locations',
        'utilization_percentage',
        'density_per_sqm',
        'density_per_cbm',
        'item_count',
        'weight_total',
        'utilization_by_category'
    ];

    protected $casts = [
        'snapshot_time' => 'datetime',
        'occupied_area' => 'decimal:2',
        'occupied_volume' => 'decimal:2',
        'utilization_percentage' => 'decimal:2',
        'density_per_sqm' => 'decimal:2',
        'density_per_cbm' => 'decimal:2',
        'weight_total' => 'decimal:2',
        'utilization_by_category' => 'array'
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
    public function scopeForZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    public function scopeForAisle($query, $aisleId)
    {
        return $query->where('aisle_id', $aisleId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('snapshot_time', [$startDate, $endDate]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('snapshot_time', 'desc');
    }

    // Methods
    public function calculateDensities()
    {
        if ($this->occupied_area > 0) {
            $this->density_per_sqm = $this->item_count / $this->occupied_area;
        }
        
        if ($this->occupied_volume > 0) {
            $this->density_per_cbm = $this->item_count / $this->occupied_volume;
        }
        
        $this->save();
    }

    public function getUtilizationStatus()
    {
        if ($this->utilization_percentage >= 95) {
            return 'critical';
        } elseif ($this->utilization_percentage >= 85) {
            return 'high';
        } elseif ($this->utilization_percentage >= 70) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    public function getAvailableSpace()
    {
        return [
            'available_locations' => $this->total_locations - $this->occupied_locations,
            'available_area' => $this->zone->usable_area - $this->occupied_area,
            'available_volume' => $this->zone->usable_volume - $this->occupied_volume
        ];
    }
}