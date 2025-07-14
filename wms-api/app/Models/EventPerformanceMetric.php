<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventPerformanceMetric extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_name',
        'measured_at',
        'processing_time',
        'status',
        'error_message',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'measured_at' => 'datetime',
        'processing_time' => 'float',
        'metadata' => 'array',
    ];

    /**
     * Scope a query to only include metrics for a specific event.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $eventName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEvent($query, $eventName)
    {
        return $query->where('event_name', $eventName);
    }

    /**
     * Scope a query to only include metrics with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include metrics from a specific time period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \DateTime  $startDate
     * @param  \DateTime|null  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInPeriod($query, $startDate, $endDate = null)
    {
        $query->where('measured_at', '>=', $startDate);
        
        if ($endDate) {
            $query->where('measured_at', '<=', $endDate);
        }
        
        return $query;
    }

    /**
     * Scope a query to only include successful metrics.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include failed metrics.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', '!=', 'success');
    }
}