<?php

namespace App\Models\Metrics;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboundMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'date',
        'total_receipts',
        'total_lines_received',
        'total_units_received',
        'receiving_accuracy',
        'dock_to_stock_time',
        'putaway_tasks_completed',
        'putaway_accuracy',
        'receiving_cost_per_line',
        'receiving_cost_per_unit',
        'receipts_processed_per_hour',
        'labor_hours',
        'unloading_time_per_truck',
        'trucks_received',
        'pallets_received',
        'damaged_items_received',
        'vendor_compliance_issues',
        'receiving_utilization',
    ];

    protected $casts = [
        'date' => 'date',
        'receiving_accuracy' => 'decimal:2',
        'dock_to_stock_time' => 'decimal:2',
        'putaway_accuracy' => 'decimal:2',
        'receiving_cost_per_line' => 'decimal:2',
        'receiving_cost_per_unit' => 'decimal:2',
        'unloading_time_per_truck' => 'decimal:2',
        'receiving_utilization' => 'decimal:2',
    ];

    /**
     * Get the warehouse that owns this inbound metric.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}