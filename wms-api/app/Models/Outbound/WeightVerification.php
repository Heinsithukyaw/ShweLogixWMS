<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

class WeightVerification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'packed_carton_id',
        'expected_weight_kg',
        'actual_weight_kg',
        'weight_difference_kg',
        'weight_difference_percentage',
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
        'expected_weight_kg' => 'decimal:3',
        'actual_weight_kg' => 'decimal:3',
        'weight_difference_kg' => 'decimal:3',
        'weight_difference_percentage' => 'decimal:2',
        'tolerance_percentage' => 'decimal:2',
        'verified_at' => 'datetime'
    ];

    /**
     * Get the packed carton that owns the weight verification.
     */
    public function packedCarton()
    {
        return $this->belongsTo(PackedCarton::class);
    }

    /**
     * Get the employee who verified the weight.
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
     * Check if the weight is within tolerance.
     *
     * @return bool
     */
    public function isWithinTolerance()
    {
        return $this->weight_difference_percentage <= $this->tolerance_percentage;
    }

    /**
     * Get the variance type (over/under weight).
     *
     * @return string
     */
    public function getVarianceType()
    {
        if ($this->actual_weight_kg > $this->expected_weight_kg) {
            return 'overweight';
        } elseif ($this->actual_weight_kg < $this->expected_weight_kg) {
            return 'underweight';
        } else {
            return 'exact';
        }
    }
}