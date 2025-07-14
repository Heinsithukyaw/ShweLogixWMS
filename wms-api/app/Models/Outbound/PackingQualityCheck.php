<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

class PackingQualityCheck extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'packed_carton_id',
        'quality_checker_id',
        'quality_criteria',
        'check_results',
        'overall_result',
        'quality_score',
        'defects_found',
        'corrective_actions',
        'requires_repack',
        'checked_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quality_criteria' => 'json',
        'check_results' => 'json',
        'quality_score' => 'decimal:2',
        'requires_repack' => 'boolean',
        'checked_at' => 'datetime'
    ];

    /**
     * Get the packed carton that owns the quality check.
     */
    public function packedCarton()
    {
        return $this->belongsTo(PackedCarton::class);
    }

    /**
     * Get the employee who performed the quality check.
     */
    public function qualityChecker()
    {
        return $this->belongsTo(Employee::class, 'quality_checker_id');
    }

    /**
     * Scope a query to only include passed quality checks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePassed($query)
    {
        return $query->where('overall_result', 'passed');
    }

    /**
     * Scope a query to only include failed quality checks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('overall_result', 'failed');
    }

    /**
     * Scope a query to only include conditional quality checks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConditional($query)
    {
        return $query->where('overall_result', 'conditional');
    }

    /**
     * Scope a query to only include quality checks that require repacking.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequiresRepack($query)
    {
        return $query->where('requires_repack', true);
    }

    /**
     * Calculate the pass rate of the quality criteria.
     *
     * @return float|null
     */
    public function calculatePassRate()
    {
        $criteria = json_decode($this->quality_criteria, true);
        $results = json_decode($this->check_results, true);
        
        if (is_array($criteria) && is_array($results) && count($criteria) > 0) {
            $passedCount = 0;
            
            foreach ($results as $key => $result) {
                if ($result === true || $result === 'passed') {
                    $passedCount++;
                }
            }
            
            return ($passedCount / count($criteria)) * 100;
        }
        
        return null;
    }

    /**
     * Get the count of failed criteria.
     *
     * @return int|null
     */
    public function getFailedCount()
    {
        $results = json_decode($this->check_results, true);
        
        if (is_array($results)) {
            $failedCount = 0;
            
            foreach ($results as $key => $result) {
                if ($result === false || $result === 'failed') {
                    $failedCount++;
                }
            }
            
            return $failedCount;
        }
        
        return null;
    }

    /**
     * Check if the quality check has any critical failures.
     *
     * @return bool
     */
    public function hasCriticalFailures()
    {
        $criteria = json_decode($this->quality_criteria, true);
        $results = json_decode($this->check_results, true);
        
        if (is_array($criteria) && is_array($results)) {
            foreach ($criteria as $key => $criterion) {
                if (isset($criterion['critical']) && $criterion['critical'] === true) {
                    if (isset($results[$key]) && ($results[$key] === false || $results[$key] === 'failed')) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
}