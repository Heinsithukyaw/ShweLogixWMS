<?php

namespace App\Models\Metrics;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'date',
        'total_sku_count',
        'total_inventory_units',
        'inventory_accuracy',
        'inventory_turnover',
        'days_on_hand',
        'storage_utilization',
        'location_accuracy',
        'stockouts_count',
        'slow_moving_items',
        'obsolete_inventory_units',
        'obsolete_inventory_value',
        'cycle_count_adjustments',
        'cycle_count_accuracy',
        'inventory_adjustments',
        'inventory_shrinkage',
        'inventory_value',
        'damaged_inventory_units',
        'damaged_inventory_value',
        'expired_inventory_units',
        'expired_inventory_value',
    ];

    protected $casts = [
        'date' => 'date',
        'inventory_accuracy' => 'decimal:2',
        'inventory_turnover' => 'decimal:2',
        'storage_utilization' => 'decimal:2',
        'location_accuracy' => 'decimal:2',
        'obsolete_inventory_value' => 'decimal:2',
        'cycle_count_accuracy' => 'decimal:2',
        'inventory_shrinkage' => 'decimal:2',
        'inventory_value' => 'decimal:2',
        'damaged_inventory_value' => 'decimal:2',
        'expired_inventory_value' => 'decimal:2',
    ];

    /**
     * Get the warehouse that owns this inventory metric.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}