<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventBacklogAlert extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'queue_name',
        'queue_size',
        'severity',
        'is_resolved',
        'resolved_at',
        'detected_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'queue_size' => 'integer',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'detected_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active (unresolved) alerts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope a query to only include resolved alerts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    /**
     * Scope a query to only include alerts for a specific queue.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $queueName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForQueue($query, $queueName)
    {
        return $query->where('queue_name', $queueName);
    }

    /**
     * Scope a query to only include alerts with a specific severity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $severity
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to only include alerts detected within a specific time period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \DateTime  $startDate
     * @param  \DateTime|null  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDetectedInPeriod($query, $startDate, $endDate = null)
    {
        $query->where('detected_at', '>=', $startDate);
        
        if ($endDate) {
            $query->where('detected_at', '<=', $endDate);
        }
        
        return $query;
    }

    /**
     * Mark the alert as resolved.
     *
     * @return bool
     */
    public function markAsResolved()
    {
        $this->is_resolved = true;
        $this->resolved_at = now();
        
        return $this->save();
    }
}