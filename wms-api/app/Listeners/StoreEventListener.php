<?php

namespace App\Listeners;

use App\Events\BaseEvent;
use App\Services\EventLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class StoreEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  BaseEvent  $event
     * @return void
     */
    public function handle(BaseEvent $event)
    {
        try {
            // Store the event in the database
            $eventLog = EventLogService::storeEvent($event);
            
            if ($eventLog) {
                Log::info('Event stored in database', [
                    'event_id' => $eventLog->id,
                    'event_name' => $event->getName(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to store event', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
        }
    }
}