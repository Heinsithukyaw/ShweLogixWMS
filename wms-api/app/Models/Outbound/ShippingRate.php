<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShippingCarrier;

class ShippingRate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shipping_carrier_id',
        'service_code',
        'service_name',
        'origin_zip',
        'destination_zip',
        'weight_from_kg',
        'weight_to_kg',
        'base_rate',
        'fuel_surcharge_rate',
        'additional_charges',
        'transit_days',
        'is_active',
        'effective_date',
        'expiry_date'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'weight_from_kg' => 'decimal:3',
        'weight_to_kg' => 'decimal:3',
        'base_rate' => 'decimal:2',
        'fuel_surcharge_rate' => 'decimal:4',
        'additional_charges' => 'json',
        'transit_days' => 'integer',
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'expiry_date' => 'date'
    ];

    /**
     * Get the shipping carrier that owns the rate.
     */
    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'shipping_carrier_id');
    }

    /**
     * Scope a query to only include active rates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include rates for a specific carrier.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $carrierId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCarrier($query, $carrierId)
    {
        return $query->where('shipping_carrier_id', $carrierId);
    }

    /**
     * Scope a query to only include rates for a specific service.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $serviceCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForService($query, $serviceCode)
    {
        return $query->where('service_code', $serviceCode);
    }

    /**
     * Scope a query to only include rates for a specific weight range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $weight
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForWeight($query, $weight)
    {
        return $query->where('weight_from_kg', '<=', $weight)
                     ->where('weight_to_kg', '>=', $weight);
    }

    /**
     * Scope a query to only include rates for a specific route.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $originZip
     * @param  string  $destinationZip
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRoute($query, $originZip, $destinationZip)
    {
        return $query->where('origin_zip', $originZip)
                     ->where('destination_zip', $destinationZip);
    }

    /**
     * Calculate the total rate including fuel surcharge.
     *
     * @return float
     */
    public function calculateTotalRate()
    {
        return $this->base_rate * (1 + $this->fuel_surcharge_rate);
    }

    /**
     * Calculate the total rate including all charges.
     *
     * @param  array  $additionalServices
     * @return float
     */
    public function calculateTotalRateWithCharges($additionalServices = [])
    {
        $totalRate = $this->calculateTotalRate();
        $additionalCharges = json_decode($this->additional_charges, true) ?? [];
        
        foreach ($additionalServices as $service) {
            if (isset($additionalCharges[$service])) {
                $totalRate += $additionalCharges[$service];
            }
        }
        
        return $totalRate;
    }

    /**
     * Check if the rate is current (within effective and expiry dates).
     *
     * @return bool
     */
    public function isCurrent()
    {
        $today = now()->startOfDay();
        
        return $this->is_active &&
               $this->effective_date->lte($today) &&
               (!$this->expiry_date || $this->expiry_date->gte($today));
    }
}