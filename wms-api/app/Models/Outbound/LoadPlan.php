<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShippingCarrier;
use App\Models\User;

class LoadPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'load_plan_number',
        'shipping_carrier_id',
        'vehicle_type',
        'vehicle_id',
        'shipment_ids',
        'load_status',
        'total_weight_kg',
        'total_volume_cm3',
        'vehicle_capacity_weight_kg',
        'vehicle_capacity_volume_cm3',
        'utilization_weight_pct',
        'utilization_volume_pct',
        'loading_sequence',
        'planned_departure_date',
        'planned_departure_time',
        'actual_departure_time',
        'loading_notes',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'shipment_ids' => 'json',
        'total_weight_kg' => 'decimal:3',
        'total_volume_cm3' => 'decimal:2',
        'vehicle_capacity_weight_kg' => 'decimal:3',
        'vehicle_capacity_volume_cm3' => 'decimal:2',
        'utilization_weight_pct' => 'decimal:2',
        'utilization_volume_pct' => 'decimal:2',
        'loading_sequence' => 'json',
        'planned_departure_date' => 'date',
        'actual_departure_time' => 'datetime'
    ];

    /**
     * Get the shipping carrier that owns the load plan.
     */
    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'shipping_carrier_id');
    }

    /**
     * Get the user who created the load plan.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the shipments for the load plan.
     */
    public function shipments()
    {
        $shipmentIds = json_decode($this->shipment_ids, true);
        
        if (is_array($shipmentIds)) {
            return Shipment::whereIn('id', $shipmentIds)->get();
        }
        
        return collect();
    }

    /**
     * Get the dock schedule for the load plan.
     */
    public function dockSchedule()
    {
        return $this->hasOne(DockSchedule::class);
    }

    /**
     * Get the loading confirmation for the load plan.
     */
    public function loadingConfirmation()
    {
        return $this->hasOne(LoadingConfirmation::class);
    }

    /**
     * Scope a query to only include planned load plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePlanned($query)
    {
        return $query->where('load_status', 'planned');
    }

    /**
     * Scope a query to only include loading load plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLoading($query)
    {
        return $query->where('load_status', 'loading');
    }

    /**
     * Scope a query to only include loaded load plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLoaded($query)
    {
        return $query->where('load_status', 'loaded');
    }

    /**
     * Scope a query to only include dispatched load plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDispatched($query)
    {
        return $query->where('load_status', 'dispatched');
    }

    /**
     * Scope a query to only include delivered load plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDelivered($query)
    {
        return $query->where('load_status', 'delivered');
    }

    /**
     * Get the shipment count.
     *
     * @return int
     */
    public function getShipmentCount()
    {
        $shipmentIds = json_decode($this->shipment_ids, true);
        
        if (is_array($shipmentIds)) {
            return count($shipmentIds);
        }
        
        return 0;
    }

    /**
     * Check if the load plan is overweight.
     *
     * @return bool
     */
    public function isOverweight()
    {
        return $this->total_weight_kg > $this->vehicle_capacity_weight_kg;
    }

    /**
     * Check if the load plan is over volume.
     *
     * @return bool
     */
    public function isOverVolume()
    {
        return $this->total_volume_cm3 > $this->vehicle_capacity_volume_cm3;
    }

    /**
     * Get the remaining weight capacity.
     *
     * @return float
     */
    public function getRemainingWeightCapacity()
    {
        return max(0, $this->vehicle_capacity_weight_kg - $this->total_weight_kg);
    }

    /**
     * Get the remaining volume capacity.
     *
     * @return float
     */
    public function getRemainingVolumeCapacity()
    {
        return max(0, $this->vehicle_capacity_volume_cm3 - $this->total_volume_cm3);
    }
}