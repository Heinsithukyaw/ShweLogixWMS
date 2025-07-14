<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

class PackingValidation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'packed_carton_id',
        'validation_type',
        'validation_status',
        'expected_value',
        'actual_value',
        'tolerance_percentage',
        'validation_notes',
        'validation_data',
        'validated_by',
        'validated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expected_value' => 'decimal:3',
        'actual_value' => 'decimal:3',
        'tolerance_percentage' => 'decimal:2',
        'validation_data' => 'json',
        'validated_at' => 'datetime'
    ];

    /**
     * Get the packed carton that owns the validation.
     */
    public function packedCarton()
    {
        return $this->belongsTo(PackedCarton::class);
    }

    /**
     * Get the employee who validated the carton.
     */
    public function validator()
    {
        return $this->belongsTo(Employee::class, 'validated_by');
    }

    /**
     * Scope a query to only include passed validations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePassed($query)
    {
        return $query->where('validation_status', 'passed');
    }

    /**
     * Scope a query to only include failed validations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('validation_status', 'failed');
    }

    /**
     * Scope a query to only include warning validations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWarning($query)
    {
        return $query->where('validation_status', 'warning');
    }

    /**
     * Scope a query to only include weight validations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWeight($query)
    {
        return $query->where('validation_type', 'weight');
    }

    /**
     * Scope a query to only include dimension validations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDimension($query)
    {
        return $query->where('validation_type', 'dimension');
    }

    /**
     * Scope a query to only include content validations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeContent($query)
    {
        return $query->where('validation_type', 'content');
    }

    /**
     * Scope a query to only include quality validations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeQuality($query)
    {
        return $query->where('validation_type', 'quality');
    }

    /**
     * Calculate the difference between expected and actual values.
     *
     * @return float|null
     */
    public function calculateDifference()
    {
        if ($this->expected_value !== null && $this->actual_value !== null) {
            return abs($this->expected_value - $this->actual_value);
        }
        
        return null;
    }

    /**
     * Calculate the difference percentage between expected and actual values.
     *
     * @return float|null
     */
    public function calculateDifferencePercentage()
    {
        if ($this->expected_value !== null && $this->actual_value !== null && $this->expected_value != 0) {
            return (abs($this->expected_value - $this->actual_value) / $this->expected_value) * 100;
        }
        
        return null;
    }

    /**
     * Check if the validation is within tolerance.
     *
     * @return bool|null
     */
    public function isWithinTolerance()
    {
        $differencePercentage = $this->calculateDifferencePercentage();
        
        if ($differencePercentage !== null && $this->tolerance_percentage !== null) {
            return $differencePercentage <= $this->tolerance_percentage;
        }
        
        return null;
    }
}