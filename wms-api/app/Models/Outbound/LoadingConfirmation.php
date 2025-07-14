<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

class LoadingConfirmation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'load_plan_id',
        'dock_schedule_id',
        'loaded_shipments',
        'actual_weight_kg',
        'total_pieces',
        'loading_method',
        'loading_supervisor_id',
        'driver_signature',
        'loading_photos',
        'loading_notes',
        'loading_started_at',
        'loading_completed_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'loaded_shipments' => 'json',
        'actual_weight_kg' => 'decimal:3',
        'total_pieces' => 'integer',
        'loading_photos' => 'json',
        'loading_started_at' => 'datetime',
        'loading_completed_at' => 'datetime'
    ];

    /**
     * Get the load plan that owns the loading confirmation.
     */
    public function loadPlan()
    {
        return $this->belongsTo(LoadPlan::class);
    }

    /**
     * Get the dock schedule that owns the loading confirmation.
     */
    public function dockSchedule()
    {
        return $this->belongsTo(DockSchedule::class);
    }

    /**
     * Get the employee who supervised the loading.
     */
    public function loadingSupervisor()
    {
        return $this->belongsTo(Employee::class, 'loading_supervisor_id');
    }

    /**
     * Get the shipments that were loaded.
     */
    public function loadedShipments()
    {
        $shipmentIds = json_decode($this->loaded_shipments, true);
        
        if (is_array($shipmentIds)) {
            return Shipment::whereIn('id', $shipmentIds)->get();
        }
        
        return collect();
    }

    /**
     * Scope a query to only include manual loading confirmations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeManual($query)
    {
        return $query->where('loading_method', 'manual');
    }

    /**
     * Scope a query to only include forklift loading confirmations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForklift($query)
    {
        return $query->where('loading_method', 'forklift');
    }

    /**
     * Scope a query to only include conveyor loading confirmations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConveyor($query)
    {
        return $query->where('loading_method', 'conveyor');
    }

    /**
     * Scope a query to only include automated loading confirmations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAutomated($query)
    {
        return $query->where('loading_method', 'automated');
    }

    /**
     * Calculate the loading duration in minutes.
     *
     * @return int|null
     */
    public function getLoadingDurationMinutes()
    {
        if ($this->loading_started_at && $this->loading_completed_at) {
            return $this->loading_started_at->diffInMinutes($this->loading_completed_at);
        }
        
        return null;
    }

    /**
     * Get the shipment count.
     *
     * @return int
     */
    public function getShipmentCount()
    {
        $shipmentIds = json_decode($this->loaded_shipments, true);
        
        if (is_array($shipmentIds)) {
            return count($shipmentIds);
        }
        
        return 0;
    }

    /**
     * Check if the loading has a driver signature.
     *
     * @return bool
     */
    public function hasDriverSignature()
    {
        return !empty($this->driver_signature);
    }

    /**
     * Check if the loading has photos.
     *
     * @return bool
     */
    public function hasPhotos()
    {
        $photos = json_decode($this->loading_photos, true);
        
        return is_array($photos) && count($photos) > 0;
    }

    /**
     * Get the photo count.
     *
     * @return int
     */
    public function getPhotoCount()
    {
        $photos = json_decode($this->loading_photos, true);
        
        if (is_array($photos)) {
            return count($photos);
        }
        
        return 0;
    }
}