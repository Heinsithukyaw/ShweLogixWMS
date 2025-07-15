<?php

namespace App\Services;

use App\Models\EventLog;
use App\Events\BaseEvent;
use Illuminate\Support\Facades\Log;

class EventLogService
{
    /**
     * Store an event in the database.
     *
     * @param  BaseEvent  $event
     * @return EventLog|null
     */
    public static function storeEvent(BaseEvent $event)
    {
        try {
            return EventLog::create([
                'event_name' => $event->getName(),
                'event_source' => $event->source,
                'event_version' => $event->version,
                'payload' => $event->getPayload(),
                'event_timestamp' => $event->timestamp,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store event in database', [
                'event' => $event->getName(),
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
    
    /**
     * Get events by name.
     *
     * @param  string  $eventName
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getEventsByName($eventName, $limit = 100)
    {
        return EventLog::where('event_name', $eventName)
            ->orderBy('event_timestamp', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get events by time range.
     *
     * @param  \DateTime  $startTime
     * @param  \DateTime  $endTime
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getEventsByTimeRange($startTime, $endTime, $limit = 100)
    {
        return EventLog::whereBetween('event_timestamp', [$startTime, $endTime])
            ->orderBy('event_timestamp', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get events by source.
     *
     * @param  string  $source
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getEventsBySource($source, $limit = 100)
    {
        return EventLog::where('event_source', $source)
            ->orderBy('event_timestamp', 'desc')
            ->limit($limit)
            ->get();
    }
}