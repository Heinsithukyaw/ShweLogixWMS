<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryConfirmation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shipment_id',
        'tracking_number',
        'delivery_status',
        'delivery_timestamp',
        'delivered_to',
        'delivery_location',
        'signature_data',
        'delivery_photos',
        'delivery_notes',
        'exception_details',
        'carrier_reference',
        'updated_at_carrier'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'delivery_timestamp' => 'datetime',
        'delivery_photos' => 'json',
        'exception_details' => 'json',
        'updated_at_carrier' => 'datetime'
    ];

    /**
     * Get the shipment that owns the delivery confirmation.
     */
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Scope a query to only include delivered confirmations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDelivered($query)
    {
        return $query->where('delivery_status', 'delivered');
    }

    /**
     * Scope a query to only include attempted confirmations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAttempted($query)
    {
        return $query->where('delivery_status', 'attempted');
    }

    /**
     * Scope a query to only include exception confirmations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeException($query)
    {
        return $query->where('delivery_status', 'exception');
    }

    /**
     * Scope a query to only include returned confirmations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReturned($query)
    {
        return $query->where('delivery_status', 'returned');
    }

    /**
     * Check if the delivery has a signature.
     *
     * @return bool
     */
    public function hasSignature()
    {
        return !empty($this->signature_data);
    }

    /**
     * Check if the delivery has photos.
     *
     * @return bool
     */
    public function hasPhotos()
    {
        $photos = json_decode($this->delivery_photos, true);
        
        return is_array($photos) && count($photos) > 0;
    }

    /**
     * Get the photo count.
     *
     * @return int
     */
    public function getPhotoCount()
    {
        $photos = json_decode($this->delivery_photos, true);
        
        if (is_array($photos)) {
            return count($photos);
        }
        
        return 0;
    }

    /**
     * Check if the delivery has exception details.
     *
     * @return bool
     */
    public function hasExceptionDetails()
    {
        $exceptionDetails = json_decode($this->exception_details, true);
        
        return is_array($exceptionDetails) && !empty($exceptionDetails);
    }

    /**
     * Get the exception reason.
     *
     * @return string|null
     */
    public function getExceptionReason()
    {
        $exceptionDetails = json_decode($this->exception_details, true);
        
        if (is_array($exceptionDetails) && isset($exceptionDetails['reason'])) {
            return $exceptionDetails['reason'];
        }
        
        return null;
    }

    /**
     * Get the days from shipment to delivery.
     *
     * @return int|null
     */
    public function getDaysToDelivery()
    {
        if ($this->shipment && $this->shipment->ship_date && $this->delivery_timestamp) {
            return $this->shipment->ship_date->diffInDays($this->delivery_timestamp);
        }
        
        return null;
    }

    /**
     * Check if the delivery was on time.
     *
     * @return bool|null
     */
    public function isOnTime()
    {
        if ($this->shipment && $this->shipment->expected_delivery_date && $this->delivery_timestamp) {
            return $this->delivery_timestamp->startOfDay()->lte($this->shipment->expected_delivery_date);
        }
        
        return null;
    }
}