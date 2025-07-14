<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;

class LoadingDock extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dock_code',
        'dock_name',
        'warehouse_id',
        'dock_type',
        'dock_status',
        'dock_capabilities',
        'max_vehicle_length_m',
        'max_vehicle_height_m',
        'has_dock_leveler',
        'has_dock_seal',
        'equipment_available'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'dock_capabilities' => 'json',
        'max_vehicle_length_m' => 'decimal:2',
        'max_vehicle_height_m' => 'decimal:2',
        'has_dock_leveler' => 'boolean',
        'has_dock_seal' => 'boolean',
        'equipment_available' => 'json'
    ];

    /**
     * Get the warehouse that owns the loading dock.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the dock schedules for the loading dock.
     */
    public function dockSchedules()
    {
        return $this->hasMany(DockSchedule::class);
    }

    /**
     * Scope a query to only include available loading docks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('dock_status', 'available');
    }

    /**
     * Scope a query to only include occupied loading docks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOccupied($query)
    {
        return $query->where('dock_status', 'occupied');
    }

    /**
     * Scope a query to only include maintenance loading docks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMaintenance($query)
    {
        return $query->where('dock_status', 'maintenance');
    }

    /**
     * Scope a query to only include closed loading docks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClosed($query)
    {
        return $query->where('dock_status', 'closed');
    }

    /**
     * Scope a query to only include outbound loading docks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOutbound($query)
    {
        return $query->where('dock_type', 'outbound');
    }

    /**
     * Scope a query to only include inbound loading docks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInbound($query)
    {
        return $query->where('dock_type', 'inbound');
    }

    /**
     * Scope a query to only include cross-dock loading docks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCrossDock($query)
    {
        return $query->where('dock_type', 'cross_dock');
    }

    /**
     * Check if the dock can accommodate a vehicle with the given dimensions.
     *
     * @param  float  $length
     * @param  float  $height
     * @return bool
     */
    public function canAccommodateVehicle($length, $height)
    {
        $canAccommodateLength = !$this->max_vehicle_length_m || $length <= $this->max_vehicle_length_m;
        $canAccommodateHeight = !$this->max_vehicle_height_m || $height <= $this->max_vehicle_height_m;
        
        return $canAccommodateLength && $canAccommodateHeight;
    }

    /**
     * Get the current dock schedule.
     *
     * @return \App\Models\Outbound\DockSchedule|null
     */
    public function getCurrentSchedule()
    {
        $now = now();
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');
        
        return $this->dockSchedules()
            ->where('scheduled_date', $today)
            ->where('scheduled_start_time', '<=', $currentTime)
            ->where('scheduled_end_time', '>=', $currentTime)
            ->where('appointment_status', '!=', 'cancelled')
            ->first();
    }

    /**
     * Get the next dock schedule.
     *
     * @return \App\Models\Outbound\DockSchedule|null
     */
    public function getNextSchedule()
    {
        $now = now();
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');
        
        return $this->dockSchedules()
            ->where(function($query) use ($today, $currentTime) {
                $query->where('scheduled_date', $today)
                      ->where('scheduled_start_time', '>', $currentTime);
            })
            ->orWhere('scheduled_date', '>', $today)
            ->where('appointment_status', '!=', 'cancelled')
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_start_time')
            ->first();
    }
}