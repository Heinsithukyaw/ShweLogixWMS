<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShippingCarrier;
use App\Models\BusinessParty;
use App\Models\User;

class Shipment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shipment_number',
        'sales_order_ids',
        'customer_id',
        'shipping_carrier_id',
        'service_level',
        'shipment_status',
        'shipment_type',
        'tracking_number',
        'shipping_address',
        'billing_address',
        'total_weight_kg',
        'total_volume_cm3',
        'total_cartons',
        'shipping_cost',
        'insurance_cost',
        'special_services',
        'ship_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'shipping_notes',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sales_order_ids' => 'json',
        'shipping_address' => 'json',
        'billing_address' => 'json',
        'total_weight_kg' => 'decimal:3',
        'total_volume_cm3' => 'decimal:2',
        'total_cartons' => 'integer',
        'shipping_cost' => 'decimal:2',
        'insurance_cost' => 'decimal:2',
        'special_services' => 'json',
        'ship_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date'
    ];

    /**
     * Get the shipping carrier that owns the shipment.
     */
    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'shipping_carrier_id');
    }

    /**
     * Get the customer that owns the shipment.
     */
    public function customer()
    {
        return $this->belongsTo(BusinessParty::class, 'customer_id');
    }

    /**
     * Get the user who created the shipment.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the shipping documents for the shipment.
     */
    public function documents()
    {
        return $this->hasMany(ShippingDocument::class);
    }

    /**
     * Get the shipping labels for the shipment.
     */
    public function labels()
    {
        return $this->hasMany(ShippingLabel::class);
    }

    /**
     * Get the load plan for the shipment.
     */
    public function loadPlan()
    {
        return $this->belongsToMany(LoadPlan::class, 'load_plan_shipments', 'shipment_id', 'load_plan_id');
    }

    /**
     * Get the delivery confirmation for the shipment.
     */
    public function deliveryConfirmation()
    {
        return $this->hasOne(DeliveryConfirmation::class);
    }

    /**
     * Scope a query to only include planned shipments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePlanned($query)
    {
        return $query->where('shipment_status', 'planned');
    }

    /**
     * Scope a query to only include ready shipments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReady($query)
    {
        return $query->where('shipment_status', 'ready');
    }

    /**
     * Scope a query to only include picked up shipments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePickedUp($query)
    {
        return $query->where('shipment_status', 'picked_up');
    }

    /**
     * Scope a query to only include in transit shipments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInTransit($query)
    {
        return $query->where('shipment_status', 'in_transit');
    }

    /**
     * Scope a query to only include delivered shipments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDelivered($query)
    {
        return $query->where('shipment_status', 'delivered');
    }

    /**
     * Scope a query to only include exception shipments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeException($query)
    {
        return $query->where('shipment_status', 'exception');
    }

    /**
     * Check if the shipment has all required documents.
     *
     * @return bool
     */
    public function hasAllRequiredDocuments()
    {
        $requiredDocuments = $this->getRequiredDocumentTypes();
        $existingDocuments = $this->documents->pluck('document_type')->toArray();
        
        foreach ($requiredDocuments as $docType) {
            if (!in_array($docType, $existingDocuments)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get the required document types based on shipment type.
     *
     * @return array
     */
    public function getRequiredDocumentTypes()
    {
        $requiredDocs = ['packing_slip'];
        
        if ($this->shipment_type === 'freight' || $this->shipment_type === 'ltl') {
            $requiredDocs[] = 'bill_of_lading';
        }
        
        // Add more logic for international shipments, hazmat, etc.
        
        return $requiredDocs;
    }

    /**
     * Calculate the days in transit.
     *
     * @return int|null
     */
    public function getDaysInTransit()
    {
        if ($this->ship_date && $this->actual_delivery_date) {
            return $this->ship_date->diffInDays($this->actual_delivery_date);
        }
        
        return null;
    }

    /**
     * Check if the shipment is on time.
     *
     * @return bool|null
     */
    public function isOnTime()
    {
        if ($this->expected_delivery_date && $this->actual_delivery_date) {
            return $this->actual_delivery_date->lte($this->expected_delivery_date);
        }
        
        return null;
    }
}