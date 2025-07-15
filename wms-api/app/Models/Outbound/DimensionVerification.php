<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

class DimensionVerification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'packed_carton_id',
        'expected_length_cm',
        'expected_width_cm',
        'expected_height_cm',
        'expected_volume_cm3',
        'actual_length_cm',
        'actual_width_cm',
        'actual_height_cm',
        'actual_volume_cm3',
        'volume_difference_cm3',
        'volume_difference_percentage',
        'tolerance_percentage',
        'verification_status',
        'verified_by',
        'verification_notes',
        'verified_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expected_length_cm' => 'decimal:2',
        'expected_width_cm' => 'decimal:2',
        'expected_height_cm' => 'decimal:2',
        'expected_volume_cm3' => 'decimal:2',
        'actual_length_cm' => 'decimal:2',
        'actual_width_cm' => 'decimal:2',
        'actual_height_cm' => 'decimal:2',
        'actual_volume_cm3' => 'decimal:2',
        'volume_difference_cm3' => 'decimal:2',
        'volume_difference_percentage' => 'decimal:2',
        'tolerance_percentage' => 'decimal:2',
        'verified_at' => 'datetime'
    ];

    /**
     * Get the packed carton that owns the dimension verification.
     */
    public function packedCarton()
    {
        return $this->belongsTo(PackedCarton::class);
    }

    /**
     * Get the employee who verified the dimensions.
     */
    public function verifier()
    {
        return $this->belongsTo(Employee::class, 'verified_by');
    }

    /**
     * Scope a query to only include passed verifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePassed($query)
    {
        return $query->where('verification_status', 'passed');
    }

    /**
     * Scope a query to only include failed verifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('verification_status', 'failed');
    }

    /**
     * Scope a query to only include warning verifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWarning($query)
    {
        return $query->where('verification_status', 'warning');
    }

    /**
     * Check if the dimensions are within tolerance.
     *
     * @return bool
     */
    public function isWithinTolerance()
    {
        return $this->volume_difference_percentage <= $this->tolerance_percentage;
    }

    /**
     * Get the variance type (over/under size).
     *
     * @return string
     */
    public function getVarianceType()
    {
        if ($this->actual_volume_cm3 > $this->expected_volume_cm3) {
            return 'oversized';
        } elseif ($this->actual_volume_cm3 < $this->expected_volume_cm3) {
            return 'undersized';
        } else {
            return 'exact';
        }
    }

    /**
     * Calculate the dimensional weight (if applicable).
     *
     * @param  float  $divisor
     * @return float
     */
    public function calculateDimensionalWeight($divisor = 5000)
    {
        return $this->actual_volume_cm3 / $divisor;
    }
}