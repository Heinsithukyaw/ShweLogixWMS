<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShippingCarrier;
use App\Models\User;

class DockSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'loading_dock_id',
        'load_plan_id',
        'shipping_carrier_id',
        'scheduled_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'actual_start_time',
        'actual_end_time',
        'appointment_status',
        'driver_name',
        'vehicle_license',
        'trailer_number',
        'special_instructions',
        'scheduled_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'scheduled_date' => 'date'
    ];

    /**
     * Get the loading dock that owns the dock schedule.
     */
    public function loadingDock()
    {
        return $this->belongsTo(LoadingDock::class);
    }

    /**
     * Get the load plan that owns the dock schedule.
     */
    public function loadPlan()
    {
        return $this->belongsTo(LoadPlan::class);
    }

    /**
     * Get the shipping carrier that owns the dock schedule.
     */
    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'shipping_carrier_id');
    }

    /**
     * Get the user who scheduled the dock appointment.
     */
    public function scheduler()
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    /**
     * Get the loading confirmation for the dock schedule.
     */
    public function loadingConfirmation()
    {
        return $this->hasOne(LoadingConfirmation::class);
    }

    /**
     * Scope a query to only include scheduled appointments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduled($query)
    {
        return $query->where('appointment_status', 'scheduled');
    }

    /**
     * Scope a query to only include confirmed appointments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConfirmed($query)
    {
        return $query->where('appointment_status', 'confirmed');
    }

    /**
     * Scope a query to only include in-progress appointments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInProgress($query)
    {
        return $query->where('appointment_status', 'in_progress');
    }

    /**
     * Scope a query to only include completed appointments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('appointment_status', 'completed');
    }

    /**
     * Scope a query to only include cancelled appointments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCancelled($query)
    {
        return $query->where('appointment_status', 'cancelled');
    }

    /**
     * Scope a query to only include no-show appointments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNoShow($query)
    {
        return $query->where('appointment_status', 'no_show');
    }

    /**
     * Scope a query to only include today's appointments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', now()->toDateString());
    }

    /**
     * Scope a query to only include future appointments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFuture($query)
    {
        return $query->where(function($q) {
            $q->whereDate('scheduled_date', '>', now()->toDateString())
              ->orWhere(function($q2) {
                  $q2->whereDate('scheduled_date', '=', now()->toDateString())
                     ->whereTime('scheduled_start_time', '>', now()->toTimeString());
              });
        });
    }

    /**
     * Scope a query to only include past appointments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePast($query)
    {
        return $query->where(function($q) {
            $q->whereDate('scheduled_date', '<', now()->toDateString())
              ->orWhere(function($q2) {
                  $q2->whereDate('scheduled_date', '=', now()->toDateString())
                     ->whereTime('scheduled_end_time', '<', now()->toTimeString());
              });
        });
    }

    /**
     * Calculate the scheduled duration in minutes.
     *
     * @return int
     */
    public function getScheduledDurationMinutes()
    {
        $startTime = strtotime($this->scheduled_start_time);
        $endTime = strtotime($this->scheduled_end_time);
        
        // Handle case where end time is on the next day
        if ($endTime < $startTime) {
            $endTime += 24 * 60 * 60; // Add 24 hours
        }
        
        return ($endTime - $startTime) / 60;
    }

    /**
     * Calculate the actual duration in minutes.
     *
     * @return int|null
     */
    public function getActualDurationMinutes()
    {
        if ($this->actual_start_time && $this->actual_end_time) {
            $startTime = strtotime($this->actual_start_time);
            $endTime = strtotime($this->actual_end_time);
            
            // Handle case where end time is on the next day
            if ($endTime < $startTime) {
                $endTime += 24 * 60 * 60; // Add 24 hours
            }
            
            return ($endTime - $startTime) / 60;
        }
        
        return null;
    }

    /**
     * Check if the appointment is currently active.
     *
     * @return bool
     */
    public function isActive()
    {
        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();
        
        return $this->scheduled_date->toDateString() === $today &&
               $this->scheduled_start_time <= $currentTime &&
               $this->scheduled_end_time >= $currentTime &&
               in_array($this->appointment_status, ['scheduled', 'confirmed', 'in_progress']);
    }
}