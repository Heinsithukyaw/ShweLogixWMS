<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventStatistic extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_name',
        'period_type',
        'period_key',
        'count',
        'avg_processing_time',
        'p50_processing_time',
        'p90_processing_time',
        'p99_processing_time',
        'error_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'count' => 'integer',
        'avg_processing_time' => 'float',
        'p50_processing_time' => 'float',
        'p90_processing_time' => 'float',
        'p99_processing_time' => 'float',
        'error_count' => 'integer',
    ];

    /**
     * Scope a query to only include statistics for a specific event.
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
     * Scope a query to only include statistics for a specific period type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $periodType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriodType($query, $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope a query to only include statistics for a specific time range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $startKey
     * @param  string  $endKey
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInPeriodRange($query, $startKey, $endKey)
    {
        return $query->where('period_key', '>=', $startKey)
                     ->where('period_key', '<=', $endKey);
    }

    /**
     * Get the formatted processing time for display.
     *
     * @param  float  $time
     * @return string
     */
    public static function formatProcessingTime($time)
    {
        if ($time === null) {
            return 'N/A';
        }
        
        // Convert to milliseconds for display
        return number_format($time * 1000, 2) . ' ms';
    }
}