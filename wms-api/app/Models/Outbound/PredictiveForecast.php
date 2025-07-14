<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;
use App\Models\User;

class PredictiveForecast extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'warehouse_id',
        'forecast_type',
        'forecast_period_start',
        'forecast_period_end',
        'historical_data_period_days',
        'forecast_data',
        'confidence_level',
        'model_accuracy_percentage',
        'key_factors',
        'seasonal_adjustments',
        'trend_analysis',
        'anomaly_detection',
        'recommendations',
        'generated_by',
        'generated_at',
        'last_updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'forecast_period_start' => 'date',
        'forecast_period_end' => 'date',
        'historical_data_period_days' => 'integer',
        'forecast_data' => 'json',
        'confidence_level' => 'decimal:2',
        'model_accuracy_percentage' => 'decimal:2',
        'key_factors' => 'json',
        'seasonal_adjustments' => 'json',
        'trend_analysis' => 'json',
        'anomaly_detection' => 'json',
        'recommendations' => 'json',
        'generated_at' => 'datetime',
        'last_updated_at' => 'datetime'
    ];

    /**
     * Get the warehouse that owns the forecast.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who generated the forecast.
     */
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Scope a query to only include demand forecasts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDemandForecast($query)
    {
        return $query->where('forecast_type', 'demand');
    }

    /**
     * Scope a query to only include capacity forecasts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCapacityForecast($query)
    {
        return $query->where('forecast_type', 'capacity');
    }

    /**
     * Scope a query to only include shipping volume forecasts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShippingVolumeForecast($query)
    {
        return $query->where('forecast_type', 'shipping_volume');
    }

    /**
     * Scope a query to only include labor forecasts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLaborForecast($query)
    {
        return $query->where('forecast_type', 'labor_demand');
    }

    /**
     * Scope a query to only include high-confidence forecasts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $threshold
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighConfidence($query, $threshold = 80.0)
    {
        return $query->where('confidence_level', '>=', $threshold);
    }

    /**
     * Get forecast value for a specific date.
     *
     * @param  string  $date
     * @return float|null
     */
    public function getForecastForDate($date)
    {
        $forecastData = json_decode($this->forecast_data, true);
        
        if (is_array($forecastData) && isset($forecastData[$date])) {
            return $forecastData[$date]['value'];
        }
        
        return null;
    }

    /**
     * Get forecast range for a specific date.
     *
     * @param  string  $date
     * @return array|null
     */
    public function getForecastRangeForDate($date)
    {
        $forecastData = json_decode($this->forecast_data, true);
        
        if (is_array($forecastData) && isset($forecastData[$date])) {
            return [
                'lower_bound' => $forecastData[$date]['lower_bound'] ?? null,
                'upper_bound' => $forecastData[$date]['upper_bound'] ?? null,
                'confidence_interval' => $forecastData[$date]['confidence_interval'] ?? null,
            ];
        }
        
        return null;
    }

    /**
     * Get peak demand periods.
     *
     * @return array
     */
    public function getPeakPeriods()
    {
        $forecastData = json_decode($this->forecast_data, true);
        $peaks = [];
        
        if (is_array($forecastData)) {
            $values = array_column($forecastData, 'value');
            $average = array_sum($values) / count($values);
            $threshold = $average * 1.2; // 20% above average
            
            foreach ($forecastData as $date => $data) {
                if ($data['value'] > $threshold) {
                    $peaks[] = [
                        'date' => $date,
                        'value' => $data['value'],
                        'percentage_above_average' => (($data['value'] - $average) / $average) * 100
                    ];
                }
            }
        }
        
        return $peaks;
    }

    /**
     * Get seasonal patterns.
     *
     * @return array
     */
    public function getSeasonalPatterns()
    {
        $adjustments = json_decode($this->seasonal_adjustments, true);
        
        if (is_array($adjustments)) {
            return $adjustments;
        }
        
        return [];
    }

    /**
     * Get trend direction.
     *
     * @return string
     */
    public function getTrendDirection()
    {
        $trendAnalysis = json_decode($this->trend_analysis, true);
        
        if (is_array($trendAnalysis) && isset($trendAnalysis['direction'])) {
            return $trendAnalysis['direction']; // 'increasing', 'decreasing', 'stable'
        }
        
        return 'unknown';
    }

    /**
     * Get trend strength.
     *
     * @return float|null
     */
    public function getTrendStrength()
    {
        $trendAnalysis = json_decode($this->trend_analysis, true);
        
        if (is_array($trendAnalysis) && isset($trendAnalysis['strength'])) {
            return $trendAnalysis['strength']; // 0-1 scale
        }
        
        return null;
    }

    /**
     * Get detected anomalies.
     *
     * @return array
     */
    public function getAnomalies()
    {
        $anomalies = json_decode($this->anomaly_detection, true);
        
        if (is_array($anomalies)) {
            return $anomalies;
        }
        
        return [];
    }

    /**
     * Get key influencing factors.
     *
     * @return array
     */
    public function getKeyFactors()
    {
        $factors = json_decode($this->key_factors, true);
        
        if (is_array($factors)) {
            return $factors;
        }
        
        return [];
    }

    /**
     * Get actionable recommendations.
     *
     * @return array
     */
    public function getRecommendations()
    {
        $recommendations = json_decode($this->recommendations, true);
        
        if (is_array($recommendations)) {
            return $recommendations;
        }
        
        return [];
    }

    /**
     * Check if forecast is still valid.
     *
     * @param  int  $validityDays
     * @return bool
     */
    public function isValid($validityDays = 7)
    {
        return $this->last_updated_at->diffInDays(now()) <= $validityDays;
    }

    /**
     * Get forecast accuracy category.
     *
     * @return string
     */
    public function getAccuracyCategory()
    {
        if ($this->model_accuracy_percentage >= 90) {
            return 'Excellent';
        } elseif ($this->model_accuracy_percentage >= 80) {
            return 'Good';
        } elseif ($this->model_accuracy_percentage >= 70) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }

    /**
     * Calculate total forecast for period.
     *
     * @return float
     */
    public function getTotalForecast()
    {
        $forecastData = json_decode($this->forecast_data, true);
        $total = 0;
        
        if (is_array($forecastData)) {
            foreach ($forecastData as $data) {
                $total += $data['value'];
            }
        }
        
        return $total;
    }

    /**
     * Get average daily forecast.
     *
     * @return float
     */
    public function getAverageDailyForecast()
    {
        $forecastData = json_decode($this->forecast_data, true);
        
        if (is_array($forecastData) && count($forecastData) > 0) {
            $total = $this->getTotalForecast();
            return $total / count($forecastData);
        }
        
        return 0;
    }

    /**
     * Get forecast variance.
     *
     * @return float
     */
    public function getForecastVariance()
    {
        $forecastData = json_decode($this->forecast_data, true);
        
        if (is_array($forecastData) && count($forecastData) > 1) {
            $values = array_column($forecastData, 'value');
            $mean = array_sum($values) / count($values);
            
            $variance = 0;
            foreach ($values as $value) {
                $variance += pow($value - $mean, 2);
            }
            
            return $variance / count($values);
        }
        
        return 0;
    }

    /**
     * Check if forecast indicates capacity constraints.
     *
     * @param  float  $capacityThreshold
     * @return bool
     */
    public function indicatesCapacityConstraints($capacityThreshold = 90.0)
    {
        $forecastData = json_decode($this->forecast_data, true);
        
        if (is_array($forecastData)) {
            foreach ($forecastData as $data) {
                if (isset($data['capacity_utilization']) && 
                    $data['capacity_utilization'] > $capacityThreshold) {
                    return true;
                }
            }
        }
        
        return false;
    }
}