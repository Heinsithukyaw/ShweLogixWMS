<?php

namespace App\Listeners\Notification;

use App\Events\Notification\NotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  NotificationEvent  $event
     * @return void
     */
    public function handle(NotificationEvent $event)
    {
        try {
            // Dispatch a job to process the notification asynchronously
            \App\Jobs\ProcessNotificationJob::dispatch(
                $event->type,
                $event->message,
                $event->data,
                $event->recipients
            );
            
            Log::info('Notification job dispatched', [
                'type' => $event->type,
                'message' => $event->message,
                'recipients' => count($event->recipients),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch notification job', [
                'type' => $event->type,
                'error' => $e->getMessage(),
            ]);
        }
    }
    

}