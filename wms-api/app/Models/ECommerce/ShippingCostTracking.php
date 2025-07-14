<?php

namespace App\Models\ECommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesOrder;
use App\Models\ShippingCarrier;

class ShippingCostTracking extends Model
{
    use HasFactory;

    protected $table = 'shipping_cost_tracking';

    protected $fillable = [
        'sales_order_id',
        'shipping_carrier_id',
        'service_type',
        'estimated_cost',
        'actual_cost',
        'cost_variance',
        'weight',
        'dimensions',
        'distance',
        'zone',
        'fuel_surcharge',
        'additional_fees',
        'discount_applied',
        'tracking_number',
        'cost_calculation_method',
        'cost_factors'
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'cost_variance' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions' => 'json',
        'distance' => 'decimal:2',
        'fuel_surcharge' => 'decimal:2',
        'additional_fees' => 'decimal:2',
        'discount_applied' => 'decimal:2',
        'cost_factors' => 'json'
    ];

    // Relationships
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function shippingCarrier()
    {
        return $this->belongsTo(ShippingCarrier::class);
    }

    // Methods
    public function calculateCostVariance()
    {
        if ($this->estimated_cost && $this->actual_cost) {
            $this->cost_variance = $this->actual_cost - $this->estimated_cost;
            $this->save();
        }
        
        return $this->cost_variance;
    }

    public function getVariancePercentage()
    {
        if ($this->estimated_cost > 0) {
            return ($this->cost_variance / $this->estimated_cost) * 100;
        }
        
        return 0;
    }

    public function getTotalCost()
    {
        return $this->actual_cost + $this->fuel_surcharge + $this->additional_fees - $this->discount_applied;
    }

    public function updateActualCost($actualCost, $additionalFees = 0, $fuelSurcharge = 0)
    {
        $this->actual_cost = $actualCost;
        $this->additional_fees = $additionalFees;
        $this->fuel_surcharge = $fuelSurcharge;
        $this->calculateCostVariance();
    }

    // Scopes
    public function scopeWithVariance($query)
    {
        return $query->whereNotNull('cost_variance');
    }

    public function scopeOverBudget($query)
    {
        return $query->where('cost_variance', '>', 0);
    }

    public function scopeUnderBudget($query)
    {
        return $query->where('cost_variance', '<', 0);
    }

    public function scopeByCarrier($query, $carrierId)
    {
        return $query->where('shipping_carrier_id', $carrierId);
    }
}