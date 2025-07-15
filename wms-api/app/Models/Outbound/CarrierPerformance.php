<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShippingCarrier;
use App\Models\User;

class CarrierPerformance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shipping_carrier_id',
        'analysis_period_start',
        'analysis_period_end',
        'total_shipments',
        'total_weight_kg',
        'total_cost',
        'on_time_deliveries',
        'late_deliveries',
        'damaged_shipments',
        'lost_shipments',
        'on_time_percentage',
        'damage_rate_percentage',
        'loss_rate_percentage',
        'average_transit_days',
        'average_cost_per_kg',
        'service_level_performance',
        'geographic_coverage',
        'pickup_reliability_percentage',
        'customer_satisfaction_score',
        'claims_total_amount',
        'claims_resolution_time_days',
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
        'total_shipments' => 'integer',
        'total_weight_kg' => 'decimal:3',
        'total_cost' => 'decimal:2',
        'on_time_deliveries' => 'integer',
        'late_deliveries' => 'integer',
        'damaged_shipments' => 'integer',
        'lost_shipments' => 'integer',
        'on_time_percentage' => 'decimal:2',
        'damage_rate_percentage' => 'decimal:4',
        'loss_rate_percentage' => 'decimal:4',
        'average_transit_days' => 'decimal:1',
        'average_cost_per_kg' => 'decimal:2',
        'service_level_performance' => 'json',
        'geographic_coverage' => 'json',
        'pickup_reliability_percentage' => 'decimal:2',
        'customer_satisfaction_score' => 'decimal:1',
        'claims_total_amount' => 'decimal:2',
        'claims_resolution_time_days' => 'decimal:1',
        'generated_at' => 'datetime'
    ];

    /**
     * Get the shipping carrier that owns the performance data.
     */
    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'shipping_carrier_id');
    }

    /**
     * Get the user who generated the performance data.
     */
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Scope a query to only include top-performing carriers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $threshold
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopPerforming($query, $threshold = 95.0)
    {
        return $query->where('on_time_percentage', '>=', $threshold);
    }

    /**
     * Scope a query to only include carriers with poor performance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $threshold
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePoorPerforming($query, $threshold = 85.0)
    {
        return $query->where('on_time_percentage', '<', $threshold);
    }

    /**
     * Scope a query to only include cost-effective carriers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $maxCostPerKg
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCostEffective($query, $maxCostPerKg = 5.0)
    {
        return $query->where('average_cost_per_kg', '<=', $maxCostPerKg);
    }

    /**
     * Scope a query to only include carriers with high damage rates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $threshold
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighDamageRate($query, $threshold = 1.0)
    {
        return $query->where('damage_rate_percentage', '>', $threshold);
    }

    /**
     * Calculate overall performance score (0-100).
     *
     * @return float
     */
    public function getOverallPerformanceScore()
    {
        $score = 0;
        
        // On-time delivery (40% weight)
        $score += $this->on_time_percentage * 0.4;
        
        // Damage rate (20% weight, inverted)
        $damageScore = max(0, 100 - ($this->damage_rate_percentage * 20));
        $score += $damageScore * 0.2;
        
        // Loss rate (15% weight, inverted)
        $lossScore = max(0, 100 - ($this->loss_rate_percentage * 50));
        $score += $lossScore * 0.15;
        
        // Customer satisfaction (15% weight)
        $score += ($this->customer_satisfaction_score * 20) * 0.15;
        
        // Pickup reliability (10% weight)
        $score += $this->pickup_reliability_percentage * 0.1;
        
        return min($score, 100);
    }

    /**
     * Get the carrier's best performing service level.
     *
     * @return string|null
     */
    public function getBestServiceLevel()
    {
        $performance = json_decode($this->service_level_performance, true);
        
        if (is_array($performance) && !empty($performance)) {
            $bestService = null;
            $bestScore = 0;
            
            foreach ($performance as $service => $metrics) {
                if (isset($metrics['on_time_percentage']) && $metrics['on_time_percentage'] > $bestScore) {
                    $bestScore = $metrics['on_time_percentage'];
                    $bestService = $service;
                }
            }
            
            return $bestService;
        }
        
        return null;
    }

    /**
     * Get the carrier's coverage areas.
     *
     * @return array
     */
    public function getCoverageAreas()
    {
        $coverage = json_decode($this->geographic_coverage, true);
        
        if (is_array($coverage)) {
            return array_keys($coverage);
        }
        
        return [];
    }

    /**
     * Calculate cost efficiency score.
     *
     * @return float
     */
    public function getCostEfficiencyScore()
    {
        // Lower cost per kg is better, normalize to 0-100 scale
        $maxCost = 10.0; // Assume $10/kg is the maximum acceptable cost
        
        if ($this->average_cost_per_kg >= $maxCost) {
            return 0;
        }
        
        return (($maxCost - $this->average_cost_per_kg) / $maxCost) * 100;
    }

    /**
     * Check if carrier meets SLA requirements.
     *
     * @param  float  $minOnTime
     * @param  float  $maxDamageRate
     * @param  float  $maxLossRate
     * @return bool
     */
    public function meetsSLA($minOnTime = 95.0, $maxDamageRate = 0.5, $maxLossRate = 0.1)
    {
        return $this->on_time_percentage >= $minOnTime &&
               $this->damage_rate_percentage <= $maxDamageRate &&
               $this->loss_rate_percentage <= $maxLossRate;
    }

    /**
     * Get performance trend compared to previous period.
     *
     * @param  CarrierPerformance  $previousPeriod
     * @return array
     */
    public function getPerformanceTrend(CarrierPerformance $previousPeriod)
    {
        return [
            'on_time_trend' => $this->on_time_percentage - $previousPeriod->on_time_percentage,
            'damage_trend' => $this->damage_rate_percentage - $previousPeriod->damage_rate_percentage,
            'cost_trend' => $this->average_cost_per_kg - $previousPeriod->average_cost_per_kg,
            'satisfaction_trend' => $this->customer_satisfaction_score - $previousPeriod->customer_satisfaction_score,
        ];
    }

    /**
     * Get carrier ranking category.
     *
     * @return string
     */
    public function getRankingCategory()
    {
        $score = $this->getOverallPerformanceScore();
        
        if ($score >= 90) {
            return 'Excellent';
        } elseif ($score >= 80) {
            return 'Good';
        } elseif ($score >= 70) {
            return 'Average';
        } elseif ($score >= 60) {
            return 'Below Average';
        } else {
            return 'Poor';
        }
    }

    /**
     * Check if carrier is recommended for specific service type.
     *
     * @param  string  $serviceType
     * @return bool
     */
    public function isRecommendedForService($serviceType)
    {
        $performance = json_decode($this->service_level_performance, true);
        
        if (is_array($performance) && isset($performance[$serviceType])) {
            $serviceMetrics = $performance[$serviceType];
            return isset($serviceMetrics['on_time_percentage']) && 
                   $serviceMetrics['on_time_percentage'] >= 90;
        }
        
        return false;
    }
}