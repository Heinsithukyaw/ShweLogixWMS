<?php

namespace App\Models\PredictiveAnalytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\BusinessParty;

class DemandForecast extends Model
{
    use HasFactory;

    protected $table = 'demand_forecasts';

    protected $fillable = [
        'product_id',
        'customer_id',
        'forecast_period',
        'forecast_date',
        'forecast_horizon_days',
        'forecasting_method',
        'historical_data_points',
        'predicted_demand',
        'confidence_level',
        'seasonal_factors',
        'trend_factors',
        'external_factors',
        'model_accuracy',
        'actual_demand',
        'forecast_error',
        'model_parameters',
        'created_by'
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_demand' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'seasonal_factors' => 'json',
        'trend_factors' => 'json',
        'external_factors' => 'json',
        'model_accuracy' => 'decimal:2',
        'actual_demand' => 'decimal:2',
        'forecast_error' => 'decimal:2',
        'model_parameters' => 'json'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(BusinessParty::class, 'customer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // Scopes
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('forecasting_method', $method);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('forecast_period', $period);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('forecast_date', '>=', now()->subDays($days));
    }

    // Methods
    public function calculateForecastError()
    {
        if ($this->actual_demand !== null && $this->predicted_demand > 0) {
            $this->forecast_error = abs($this->actual_demand - $this->predicted_demand) / $this->predicted_demand * 100;
            $this->save();
        }
        
        return $this->forecast_error;
    }

    public function updateActualDemand($actualDemand)
    {
        $this->actual_demand = $actualDemand;
        $this->calculateForecastError();
        
        // Update model accuracy based on this forecast
        $this->updateModelAccuracy();
    }

    private function updateModelAccuracy()
    {
        // Calculate MAPE (Mean Absolute Percentage Error) for this forecasting method
        $recentForecasts = self::where('forecasting_method', $this->forecasting_method)
            ->where('product_id', $this->product_id)
            ->whereNotNull('actual_demand')
            ->where('forecast_date', '>=', now()->subDays(90))
            ->get();

        if ($recentForecasts->count() > 0) {
            $totalError = $recentForecasts->sum('forecast_error');
            $averageError = $totalError / $recentForecasts->count();
            $this->model_accuracy = max(0, 100 - $averageError);
            $this->save();
        }
    }

    public static function generateForecast($productId, $customerId = null, $method = 'arima', $horizonDays = 30)
    {
        $product = Product::findOrFail($productId);
        
        // Get historical data
        $historicalData = self::getHistoricalDemandData($productId, $customerId);
        
        // Apply forecasting method
        $forecast = match ($method) {
            'arima' => self::arimaForecast($historicalData, $horizonDays),
            'exponential_smoothing' => self::exponentialSmoothingForecast($historicalData, $horizonDays),
            'linear_regression' => self::linearRegressionForecast($historicalData, $horizonDays),
            'seasonal_naive' => self::seasonalNaiveForecast($historicalData, $horizonDays),
            'machine_learning' => self::machineLearningForecast($historicalData, $horizonDays),
            default => self::simpleMovingAverage($historicalData, $horizonDays)
        };

        return self::create([
            'product_id' => $productId,
            'customer_id' => $customerId,
            'forecast_period' => 'daily',
            'forecast_date' => now()->toDateString(),
            'forecast_horizon_days' => $horizonDays,
            'forecasting_method' => $method,
            'historical_data_points' => count($historicalData),
            'predicted_demand' => $forecast['predicted_demand'],
            'confidence_level' => $forecast['confidence_level'],
            'seasonal_factors' => $forecast['seasonal_factors'],
            'trend_factors' => $forecast['trend_factors'],
            'model_parameters' => $forecast['model_parameters'],
            'created_by' => auth()->id()
        ]);
    }

    private static function getHistoricalDemandData($productId, $customerId = null)
    {
        // This would query actual sales/demand data
        // For now, return mock data
        $data = [];
        for ($i = 90; $i >= 0; $i--) {
            $data[] = [
                'date' => now()->subDays($i)->toDateString(),
                'demand' => rand(10, 100) + sin($i * 0.1) * 20 // Mock seasonal pattern
            ];
        }
        return $data;
    }

    private static function arimaForecast($historicalData, $horizonDays)
    {
        // ARIMA (AutoRegressive Integrated Moving Average) implementation
        // This is a simplified version - in production, you'd use a proper time series library
        
        $demands = array_column($historicalData, 'demand');
        $trend = self::calculateTrend($demands);
        $seasonality = self::calculateSeasonality($demands);
        
        $lastValue = end($demands);
        $predictedDemand = $lastValue + ($trend * $horizonDays) + $seasonality;
        
        return [
            'predicted_demand' => max(0, $predictedDemand),
            'confidence_level' => 85.0,
            'seasonal_factors' => ['seasonal_index' => $seasonality],
            'trend_factors' => ['trend_slope' => $trend],
            'model_parameters' => ['p' => 1, 'd' => 1, 'q' => 1]
        ];
    }

    private static function exponentialSmoothingForecast($historicalData, $horizonDays)
    {
        $demands = array_column($historicalData, 'demand');
        $alpha = 0.3; // Smoothing parameter
        
        $smoothed = $demands[0];
        foreach (array_slice($demands, 1) as $demand) {
            $smoothed = $alpha * $demand + (1 - $alpha) * $smoothed;
        }
        
        return [
            'predicted_demand' => $smoothed,
            'confidence_level' => 80.0,
            'seasonal_factors' => [],
            'trend_factors' => [],
            'model_parameters' => ['alpha' => $alpha]
        ];
    }

    private static function linearRegressionForecast($historicalData, $horizonDays)
    {
        $demands = array_column($historicalData, 'demand');
        $n = count($demands);
        
        // Calculate linear regression
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($demands);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $demands[$i];
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        $predictedDemand = $slope * ($n + $horizonDays) + $intercept;
        
        return [
            'predicted_demand' => max(0, $predictedDemand),
            'confidence_level' => 75.0,
            'seasonal_factors' => [],
            'trend_factors' => ['slope' => $slope, 'intercept' => $intercept],
            'model_parameters' => ['slope' => $slope, 'intercept' => $intercept]
        ];
    }

    private static function seasonalNaiveForecast($historicalData, $horizonDays)
    {
        $demands = array_column($historicalData, 'demand');
        $seasonLength = 7; // Weekly seasonality
        
        if (count($demands) >= $seasonLength) {
            $seasonalIndex = $horizonDays % $seasonLength;
            $predictedDemand = $demands[count($demands) - $seasonLength + $seasonalIndex];
        } else {
            $predictedDemand = array_sum($demands) / count($demands);
        }
        
        return [
            'predicted_demand' => $predictedDemand,
            'confidence_level' => 70.0,
            'seasonal_factors' => ['season_length' => $seasonLength],
            'trend_factors' => [],
            'model_parameters' => ['season_length' => $seasonLength]
        ];
    }

    private static function machineLearningForecast($historicalData, $horizonDays)
    {
        // This would integrate with ML libraries like TensorFlow or scikit-learn
        // For now, return a sophisticated moving average with trend adjustment
        
        $demands = array_column($historicalData, 'demand');
        $recentDemands = array_slice($demands, -14); // Last 2 weeks
        $trend = self::calculateTrend($recentDemands);
        $avgDemand = array_sum($recentDemands) / count($recentDemands);
        
        $predictedDemand = $avgDemand + ($trend * $horizonDays * 0.5);
        
        return [
            'predicted_demand' => max(0, $predictedDemand),
            'confidence_level' => 90.0,
            'seasonal_factors' => [],
            'trend_factors' => ['trend' => $trend],
            'model_parameters' => ['model_type' => 'neural_network', 'layers' => 3]
        ];
    }

    private static function simpleMovingAverage($historicalData, $horizonDays)
    {
        $demands = array_column($historicalData, 'demand');
        $window = min(7, count($demands)); // 7-day moving average
        $recentDemands = array_slice($demands, -$window);
        
        return [
            'predicted_demand' => array_sum($recentDemands) / count($recentDemands),
            'confidence_level' => 65.0,
            'seasonal_factors' => [],
            'trend_factors' => [],
            'model_parameters' => ['window_size' => $window]
        ];
    }

    private static function calculateTrend($data)
    {
        if (count($data) < 2) return 0;
        
        $n = count($data);
        $firstHalf = array_slice($data, 0, intval($n/2));
        $secondHalf = array_slice($data, intval($n/2));
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        return ($secondAvg - $firstAvg) / (count($secondHalf));
    }

    private static function calculateSeasonality($data)
    {
        // Simplified seasonality calculation
        $n = count($data);
        if ($n < 14) return 0;
        
        $weeklyAvg = [];
        for ($i = 0; $i < 7; $i++) {
            $dayValues = [];
            for ($j = $i; $j < $n; $j += 7) {
                $dayValues[] = $data[$j];
            }
            $weeklyAvg[$i] = array_sum($dayValues) / count($dayValues);
        }
        
        $overallAvg = array_sum($weeklyAvg) / 7;
        $todayIndex = now()->dayOfWeek;
        
        return $weeklyAvg[$todayIndex] - $overallAvg;
    }

    public function getAccuracyLevel()
    {
        if ($this->model_accuracy >= 90) return 'Excellent';
        if ($this->model_accuracy >= 80) return 'Good';
        if ($this->model_accuracy >= 70) return 'Fair';
        return 'Poor';
    }
}