<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;
use App\Models\User;

class QualityMetric extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date_range_start',
        'date_range_end',
        'warehouse_id',
        'total_quality_checks',
        'passed_checks',
        'failed_checks',
        'conditional_checks',
        'pass_rate_percentage',
        'average_quality_score',
        'total_exceptions',
        'exceptions_by_type',
        'average_resolution_time_hours',
        'generated_by',
        'generated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date_range_start' => 'date',
        'date_range_end' => 'date',
        'total_quality_checks' => 'integer',
        'passed_checks' => 'integer',
        'failed_checks' => 'integer',
        'conditional_checks' => 'integer',
        'pass_rate_percentage' => 'decimal:2',
        'average_quality_score' => 'decimal:2',
        'total_exceptions' => 'integer',
        'exceptions_by_type' => 'json',
        'average_resolution_time_hours' => 'decimal:2',
        'generated_at' => 'datetime'
    ];

    /**
     * Get the warehouse that owns the quality metric.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who generated the metric.
     */
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Calculate the fail rate percentage.
     *
     * @return float
     */
    public function getFailRatePercentage()
    {
        if ($this->total_quality_checks > 0) {
            return ($this->failed_checks / $this->total_quality_checks) * 100;
        }
        
        return 0;
    }

    /**
     * Calculate the conditional rate percentage.
     *
     * @return float
     */
    public function getConditionalRatePercentage()
    {
        if ($this->total_quality_checks > 0) {
            return ($this->conditional_checks / $this->total_quality_checks) * 100;
        }
        
        return 0;
    }

    /**
     * Get the most common exception type.
     *
     * @return string|null
     */
    public function getMostCommonExceptionType()
    {
        $exceptionsByType = json_decode($this->exceptions_by_type, true);
        
        if (is_array($exceptionsByType) && !empty($exceptionsByType)) {
            return array_keys($exceptionsByType, max($exceptionsByType))[0];
        }
        
        return null;
    }

    /**
     * Get the exception count for a specific type.
     *
     * @param  string  $type
     * @return int
     */
    public function getExceptionCountByType($type)
    {
        $exceptionsByType = json_decode($this->exceptions_by_type, true);
        
        if (is_array($exceptionsByType) && isset($exceptionsByType[$type])) {
            return $exceptionsByType[$type];
        }
        
        return 0;
    }

    /**
     * Check if the quality metrics meet the target.
     *
     * @param  float  $targetPassRate
     * @return bool
     */
    public function meetsTarget($targetPassRate = 95.0)
    {
        return $this->pass_rate_percentage >= $targetPassRate;
    }
}