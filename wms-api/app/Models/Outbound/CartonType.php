<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartonType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'carton_code',
        'carton_name',
        'length_cm',
        'width_cm',
        'height_cm',
        'max_weight_kg',
        'tare_weight_kg',
        'volume_cm3',
        'carton_material',
        'cost_per_unit',
        'usage_rules',
        'supplier',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'length_cm' => 'decimal:2',
        'width_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'max_weight_kg' => 'decimal:2',
        'tare_weight_kg' => 'decimal:3',
        'volume_cm3' => 'decimal:2',
        'cost_per_unit' => 'decimal:4',
        'usage_rules' => 'json',
        'is_active' => 'boolean'
    ];

    /**
     * Get the packed cartons for the carton type.
     */
    public function packedCartons()
    {
        return $this->hasMany(PackedCarton::class);
    }

    /**
     * Scope a query to only include active carton types.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate the volume of the carton.
     *
     * @return float
     */
    public function calculateVolume()
    {
        return $this->length_cm * $this->width_cm * $this->height_cm;
    }

    /**
     * Check if the carton can fit the given dimensions.
     *
     * @param float $length
     * @param float $width
     * @param float $height
     * @return bool
     */
    public function canFitDimensions($length, $width, $height)
    {
        // Try all possible orientations
        return ($length <= $this->length_cm && $width <= $this->width_cm && $height <= $this->height_cm) ||
               ($length <= $this->length_cm && $width <= $this->height_cm && $height <= $this->width_cm) ||
               ($length <= $this->width_cm && $width <= $this->length_cm && $height <= $this->height_cm) ||
               ($length <= $this->width_cm && $width <= $this->height_cm && $height <= $this->length_cm) ||
               ($length <= $this->height_cm && $width <= $this->length_cm && $height <= $this->width_cm) ||
               ($length <= $this->height_cm && $width <= $this->width_cm && $height <= $this->length_cm);
    }

    /**
     * Check if the carton can hold the given weight.
     *
     * @param float $weight
     * @return bool
     */
    public function canHoldWeight($weight)
    {
        return $weight <= $this->max_weight_kg;
    }
}