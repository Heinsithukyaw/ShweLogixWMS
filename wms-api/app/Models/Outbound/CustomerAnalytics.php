<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusinessParty;
use App\Models\User;

class CustomerAnalytics extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'analysis_period_start',
        'analysis_period_end',
        'total_orders',
        'total_shipments',
        'total_revenue',
        'average_order_value',
        'total_weight_shipped_kg',
        'total_volume_shipped_cm3',
        'on_time_delivery_rate',
        'average_processing_time_hours',
        'return_rate_percentage',
        'preferred_carriers',
        'shipping_cost_percentage',
        'order_frequency_days',
        'seasonal_patterns',
        'geographic_distribution',
        'service_level_preferences',
        'generated_by',
        'generated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'analysis_period_start' => 'date',
        'analysis_period_end' => 'date',
        'total_orders' => 'integer',
        'total_shipments' => 'integer',
        'total_revenue' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'total_weight_shipped_kg' => 'decimal:3',
        'total_volume_shipped_cm3' => 'decimal:2',
        'on_time_delivery_rate' => 'decimal:2',
        'average_processing_time_hours' => 'decimal:2',
        'return_rate_percentage' => 'decimal:2',
        'preferred_carriers' => 'json',
        'shipping_cost_percentage' => 'decimal:2',
        'order_frequency_days' => 'decimal:1',
        'seasonal_patterns' => 'json',
        'geographic_distribution' => 'json',
        'service_level_preferences' => 'json',
        'generated_at' => 'datetime'
    ];

    /**
     * Get the customer that owns the analytics.
     */
    public function customer()
    {
        return $this->belongsTo(BusinessParty::class, 'customer_id');
    }

    /**
     * Get the user who generated the analytics.
     */
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Scope a query to only include analytics for high-value customers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $threshold
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighValue($query, $threshold = 10000)
    {
        return $query->where('total_revenue', '>=', $threshold);
    }

    /**
     * Scope a query to only include analytics for frequent customers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $minOrders
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFrequent($query, $minOrders = 10)
    {
        return $query->where('total_orders', '>=', $minOrders);
    }

    /**
     * Scope a query to only include analytics for customers with poor delivery performance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $threshold
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePoorDeliveryPerformance($query, $threshold = 90.0)
    {
        return $query->where('on_time_delivery_rate', '<', $threshold);
    }

    /**
     * Get the customer's preferred carrier.
     *
     * @return string|null
     */
    public function getPreferredCarrier()
    {
        $carriers = json_decode($this->preferred_carriers, true);
        
        if (is_array($carriers) && !empty($carriers)) {
            return array_keys($carriers, max($carriers))[0];
        }
        
        return null;
    }

    /**
     * Get the customer's peak shipping season.
     *
     * @return string|null
     */
    public function getPeakSeason()
    {
        $patterns = json_decode($this->seasonal_patterns, true);
        
        if (is_array($patterns) && !empty($patterns)) {
            return array_keys($patterns, max($patterns))[0];
        }
        
        return null;
    }

    /**
     * Calculate customer lifetime value.
     *
     * @return float
     */
    public function getLifetimeValue()
    {
        $periodDays = $this->analysis_period_start->diffInDays($this->analysis_period_end);
        $dailyRevenue = $this->total_revenue / $periodDays;
        
        // Estimate annual revenue
        $annualRevenue = $dailyRevenue * 365;
        
        // Simple LTV calculation (can be enhanced with churn rate, etc.)
        return $annualRevenue * 3; // Assuming 3-year customer lifespan
    }

    /**
     * Get customer risk score (0-100, higher is riskier).
     *
     * @return float
     */
    public function getRiskScore()
    {
        $score = 0;
        
        // Poor delivery performance increases risk
        if ($this->on_time_delivery_rate < 90) {
            $score += (90 - $this->on_time_delivery_rate) * 2;
        }
        
        // High return rate increases risk
        if ($this->return_rate_percentage > 5) {
            $score += ($this->return_rate_percentage - 5) * 3;
        }
        
        // Low order frequency increases risk
        if ($this->order_frequency_days > 30) {
            $score += ($this->order_frequency_days - 30) / 2;
        }
        
        return min($score, 100);
    }

    /**
     * Check if customer is a high-value customer.
     *
     * @param  float  $threshold
     * @return bool
     */
    public function isHighValue($threshold = 10000)
    {
        return $this->total_revenue >= $threshold;
    }

    /**
     * Check if customer has consistent ordering pattern.
     *
     * @return bool
     */
    public function hasConsistentOrdering()
    {
        return $this->order_frequency_days <= 30 && $this->total_orders >= 5;
    }
}