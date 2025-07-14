<?php

namespace App\Models\Metrics;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'date',
        'labor_efficiency',
        'labor_utilization',
        'equipment_utilization',
        'space_utilization',
        'cost_per_order',
        'cost_per_line',
        'cost_per_unit',
        'total_labor_hours',
        'total_labor_cost',
        'revenue_per_labor_hour',
        'units_per_labor_hour',
        'orders_per_labor_hour',
        'lines_per_labor_hour',
        'overtime_hours',
        'overtime_percentage',
        'safety_incidents',
        'energy_consumption',
        'energy_cost',
        'total_operating_cost',
        'cost_as_percentage_of_revenue',
        'throughput_per_square_foot',
        'revenue_per_square_foot',
        'profit_per_square_foot',
        'customer_satisfaction_score',
        'customer_complaints',
        'employee_turnover',
        'training_hours',
        'cross_trained_employees',
        'equipment_downtime',
        'maintenance_cost',
        'system_uptime',
        'system_issues',
        'average_dock_door_utilization',
        'average_equipment_utilization',
        'peak_capacity_utilization',
        'carbon_footprint',
        'waste_generated',
        'recycling_rate',
        'water_usage',
        'transportation_cost',
        'transportation_cost_per_order',
    ];

    protected $casts = [
        'date' => 'date',
        'labor_efficiency' => 'decimal:2',
        'labor_utilization' => 'decimal:2',
        'equipment_utilization' => 'decimal:2',
        'space_utilization' => 'decimal:2',
        'cost_per_order' => 'decimal:2',
        'cost_per_line' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'total_labor_hours' => 'decimal:2',
        'total_labor_cost' => 'decimal:2',
        'revenue_per_labor_hour' => 'decimal:2',
        'units_per_labor_hour' => 'decimal:2',
        'orders_per_labor_hour' => 'decimal:2',
        'lines_per_labor_hour' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_percentage' => 'decimal:2',
        'energy_consumption' => 'decimal:2',
        'energy_cost' => 'decimal:2',
        'total_operating_cost' => 'decimal:2',
        'cost_as_percentage_of_revenue' => 'decimal:2',
        'throughput_per_square_foot' => 'decimal:2',
        'revenue_per_square_foot' => 'decimal:2',
        'profit_per_square_foot' => 'decimal:2',
        'customer_satisfaction_score' => 'decimal:2',
        'employee_turnover' => 'decimal:2',
        'training_hours' => 'decimal:2',
        'equipment_downtime' => 'decimal:2',
        'maintenance_cost' => 'decimal:2',
        'system_uptime' => 'decimal:2',
        'average_dock_door_utilization' => 'decimal:2',
        'average_equipment_utilization' => 'decimal:2',
        'peak_capacity_utilization' => 'decimal:2',
        'carbon_footprint' => 'decimal:2',
        'waste_generated' => 'decimal:2',
        'recycling_rate' => 'decimal:2',
        'water_usage' => 'decimal:2',
        'transportation_cost' => 'decimal:2',
        'transportation_cost_per_order' => 'decimal:2',
    ];

    /**
     * Get the warehouse that owns this performance metric.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}