<?php

namespace App\Models\Metrics;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboundMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'date',
        'total_orders_shipped',
        'total_lines_shipped',
        'total_units_shipped',
        'order_accuracy',
        'picking_accuracy',
        'shipping_accuracy',
        'on_time_shipping',
        'order_cycle_time',
        'picking_time_per_line',
        'picking_time_per_unit',
        'packing_time_per_order',
        'orders_picked_per_hour',
        'lines_picked_per_hour',
        'units_picked_per_hour',
        'orders_packed_per_hour',
        'picking_cost_per_line',
        'picking_cost_per_order',
        'shipping_cost_per_order',
        'perfect_order_count',
        'perfect_order_percentage',
        'backorders_count',
        'backorder_rate',
        'canceled_orders',
        'returns_processed',
        'return_rate',
        'labor_hours',
        'dock_utilization',
        'trucks_loaded',
        'pallets_shipped',
        'peak_hourly_orders',
        'same_day_shipments',
        'next_day_shipments',
        'expedited_shipments',
        'late_shipments',
        'average_items_per_order',
        'average_order_value',
    ];

    protected $casts = [
        'date' => 'date',
        'order_accuracy' => 'decimal:2',
        'picking_accuracy' => 'decimal:2',
        'shipping_accuracy' => 'decimal:2',
        'on_time_shipping' => 'decimal:2',
        'order_cycle_time' => 'decimal:2',
        'picking_time_per_line' => 'decimal:2',
        'picking_time_per_unit' => 'decimal:2',
        'packing_time_per_order' => 'decimal:2',
        'picking_cost_per_line' => 'decimal:2',
        'picking_cost_per_order' => 'decimal:2',
        'shipping_cost_per_order' => 'decimal:2',
        'perfect_order_percentage' => 'decimal:2',
        'backorder_rate' => 'decimal:2',
        'return_rate' => 'decimal:2',
        'dock_utilization' => 'decimal:2',
        'average_items_per_order' => 'decimal:2',
        'average_order_value' => 'decimal:2',
    ];

    /**
     * Get the warehouse that owns this outbound metric.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}