<?php

namespace App\Models\Profitability;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostAllocation extends Model
{
    use HasFactory;

    protected $table = 'cost_allocations';

    protected $fillable = [
        'profitability_analysis_id',
        'allocation_method',
        'cost_category',
        'cost_driver',
        'total_cost',
        'allocated_amount',
        'allocation_percentage',
        'allocation_basis',
        'allocation_rules',
        'notes'
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'allocation_percentage' => 'decimal:2',
        'allocation_rules' => 'json'
    ];

    // Relationships
    public function profitabilityAnalysis()
    {
        return $this->belongsTo(ProfitabilityAnalysis::class);
    }

    // Methods
    public function calculateTraditionalAllocation($totalCostDriver, $entityCostDriver)
    {
        if ($totalCostDriver > 0) {
            $this->allocation_percentage = ($entityCostDriver / $totalCostDriver) * 100;
            $this->allocated_amount = ($this->allocation_percentage / 100) * $this->total_cost;
        }
        
        $this->save();
    }

    public function calculateABCAllocation($activities)
    {
        // Activity-Based Costing allocation
        $totalAllocated = 0;
        
        foreach ($activities as $activity) {
            $activityRate = $activity['cost'] / $activity['driver_quantity'];
            $entityConsumption = $activity['entity_consumption'];
            $allocatedCost = $activityRate * $entityConsumption;
            
            $totalAllocated += $allocatedCost;
        }
        
        $this->allocated_amount = $totalAllocated;
        $this->allocation_percentage = $this->total_cost > 0 ? ($totalAllocated / $this->total_cost) * 100 : 0;
        $this->save();
    }

    public static function getAllocationMethods()
    {
        return [
            'traditional' => 'Traditional Costing',
            'abc' => 'Activity-Based Costing',
            'direct' => 'Direct Allocation',
            'step_down' => 'Step-Down Method',
            'reciprocal' => 'Reciprocal Method'
        ];
    }

    public static function getCostDrivers()
    {
        return [
            'labor_hours' => 'Labor Hours',
            'machine_hours' => 'Machine Hours',
            'square_footage' => 'Square Footage',
            'number_of_orders' => 'Number of Orders',
            'weight' => 'Weight',
            'volume' => 'Volume',
            'revenue' => 'Revenue'
        ];
    }
}