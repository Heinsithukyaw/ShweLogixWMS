<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesOrder;
use App\Models\User;

class MultiCartonShipment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sales_order_id',
        'master_tracking_number',
        'total_cartons',
        'carton_ids',
        'total_weight_kg',
        'total_volume_cm3',
        'shipment_status',
        'shipping_labels',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_cartons' => 'integer',
        'carton_ids' => 'json',
        'total_weight_kg' => 'decimal:3',
        'total_volume_cm3' => 'decimal:2',
        'shipping_labels' => 'json'
    ];

    /**
     * Get the sales order that owns the multi-carton shipment.
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the user who created the multi-carton shipment.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the packed cartons for the multi-carton shipment.
     */
    public function packedCartons()
    {
        $cartonIds = json_decode($this->carton_ids, true);
        
        if (is_array($cartonIds)) {
            return PackedCarton::whereIn('id', $cartonIds)->get();
        }
        
        return collect();
    }

    /**
     * Scope a query to only include pending shipments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('shipment_status', 'pending');
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
     * Scope a query to only include shipped shipments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShipped($query)
    {
        return $query->where('shipment_status', 'shipped');
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
     * Check if all cartons have shipping labels.
     *
     * @return bool
     */
    public function allCartonsLabeled()
    {
        $cartonIds = json_decode($this->carton_ids, true);
        
        if (is_array($cartonIds)) {
            $labelCount = ShippingLabel::whereIn('packed_carton_id', $cartonIds)->count();
            return $labelCount === count($cartonIds);
        }
        
        return false;
    }

    /**
     * Get the count of labeled cartons.
     *
     * @return int
     */
    public function getLabeledCartonCount()
    {
        $cartonIds = json_decode($this->carton_ids, true);
        
        if (is_array($cartonIds)) {
            return ShippingLabel::whereIn('packed_carton_id', $cartonIds)->count();
        }
        
        return 0;
    }
}