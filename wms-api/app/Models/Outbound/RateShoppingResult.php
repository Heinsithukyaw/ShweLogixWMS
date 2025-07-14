<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesOrder;
use App\Models\ShippingCarrier;
use App\Models\User;

class RateShoppingResult extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sales_order_id',
        'origin_zip',
        'destination_zip',
        'total_weight_kg',
        'total_volume_cm3',
        'rate_quotes',
        'selected_carrier_id',
        'selected_service_code',
        'selected_rate',
        'selection_criteria',
        'quoted_at',
        'expires_at',
        'requested_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_weight_kg' => 'decimal:3',
        'total_volume_cm3' => 'decimal:2',
        'rate_quotes' => 'json',
        'selected_rate' => 'decimal:2',
        'selection_criteria' => 'json',
        'quoted_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    /**
     * Get the sales order that owns the rate shopping result.
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the selected carrier.
     */
    public function selectedCarrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'selected_carrier_id');
    }

    /**
     * Get the user who requested the rate shopping.
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Scope a query to only include active (non-expired) rate quotes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include rate quotes for a specific carrier.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $carrierId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCarrier($query, $carrierId)
    {
        return $query->where('selected_carrier_id', $carrierId);
    }

    /**
     * Check if the rate quote is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return now()->gt($this->expires_at);
    }

    /**
     * Get all available carriers from the rate quotes.
     *
     * @return array
     */
    public function getAvailableCarriers()
    {
        $quotes = json_decode($this->rate_quotes, true);
        $carriers = [];
        
        if (is_array($quotes)) {
            foreach ($quotes as $quote) {
                if (isset($quote['carrier_id']) && isset($quote['carrier_name'])) {
                    $carriers[$quote['carrier_id']] = $quote['carrier_name'];
                }
            }
        }
        
        return $carriers;
    }

    /**
     * Get all available services for a specific carrier.
     *
     * @param  int  $carrierId
     * @return array
     */
    public function getAvailableServices($carrierId)
    {
        $quotes = json_decode($this->rate_quotes, true);
        $services = [];
        
        if (is_array($quotes)) {
            foreach ($quotes as $quote) {
                if (isset($quote['carrier_id']) && $quote['carrier_id'] == $carrierId && isset($quote['services'])) {
                    return $quote['services'];
                }
            }
        }
        
        return $services;
    }

    /**
     * Get the rate for a specific carrier and service.
     *
     * @param  int  $carrierId
     * @param  string  $serviceCode
     * @return float|null
     */
    public function getRateForCarrierService($carrierId, $serviceCode)
    {
        $services = $this->getAvailableServices($carrierId);
        
        if (isset($services[$serviceCode]) && isset($services[$serviceCode]['cost'])) {
            return $services[$serviceCode]['cost'];
        }
        
        return null;
    }

    /**
     * Get the transit days for a specific carrier and service.
     *
     * @param  int  $carrierId
     * @param  string  $serviceCode
     * @return int|null
     */
    public function getTransitDaysForCarrierService($carrierId, $serviceCode)
    {
        $services = $this->getAvailableServices($carrierId);
        
        if (isset($services[$serviceCode]) && isset($services[$serviceCode]['transit_days'])) {
            return $services[$serviceCode]['transit_days'];
        }
        
        return null;
    }
}