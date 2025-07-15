<?php

namespace App\Jobs;

use App\Events\BaseEvent;
use App\Services\EventLogService;
use App\Services\BroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The event instance.
     *
     * @var BaseEvent
     */
    protected $event;

    /**
     * Create a new job instance.
     *
     * @param  BaseEvent  $event
     * @return void
     */
    public function __construct(BaseEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Store the event in the database
            EventLogService::storeEvent($this->event);
            
            // Determine the appropriate channel based on the event type
            $channel = $this->determineChannel();
            
            // Broadcast the event to the frontend
            BroadcastService::broadcastEvent($this->event, $channel);
            
            Log::info('Event processed successfully', [
                'event' => $this->event->getName(),
                'channel' => $channel,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process event', [
                'event' => $this->event->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Retry the job with exponential backoff
            $this->release(30);
        }
    }
    
    /**
     * Determine the appropriate channel for the event.
     *
     * @return string
     */
    protected function determineChannel()
    {
        $eventClass = get_class($this->event);
        
        if (strpos($eventClass, 'Inventory') !== false) {
            return 'inventory';
        } elseif (strpos($eventClass, 'Warehouse') !== false) {
            return 'warehouse';
        } elseif (strpos($eventClass, 'Inbound') !== false) {
            return 'inbound';
        } elseif (strpos($eventClass, 'Notification') !== false) {
            return 'notifications';
        } else {
            return 'events';
        }
    }
}