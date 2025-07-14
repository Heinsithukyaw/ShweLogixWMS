<?php

namespace App\Listeners\Warehouse;

use App\Events\Notification\NotificationEvent;
use App\Events\Warehouse\TaskStatusChangedEvent;
use App\Services\EventMonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class TaskStatusChangedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The event monitoring service.
     *
     * @var \App\Services\EventMonitoringService
     */
    protected $eventMonitoringService;

    /**
     * Create the event listener.
     *
     * @param  \App\Services\EventMonitoringService  $eventMonitoringService
     * @return void
     */
    public function __construct(EventMonitoringService $eventMonitoringService)
    {
        $this->eventMonitoringService = $eventMonitoringService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\Warehouse\TaskStatusChangedEvent  $event
     * @return void
     */
    public function handle(TaskStatusChangedEvent $event)
    {
        try {
            Log::info('Processing task status changed event', [
                'task_id' => $event->task->id,
                'task_type' => $event->taskType,
                'previous_status' => $event->previousStatus,
                'new_status' => $event->newStatus,
            ]);

            // Record the event for monitoring
            $this->eventMonitoringService->recordEvent(
                $event->getName(),
                $event->getPayload()
            );

            // Create a notification for the status change
            $this->createNotification($event);

            // Update task metrics if the task is completed
            if ($event->newStatus === 'completed') {
                $this->updateTaskMetrics($event);
            }

            Log::info('Task status changed event processed successfully', [
                'task_id' => $event->task->id,
                'task_type' => $event->taskType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process task status changed event', [
                'task_id' => $event->task->id,
                'task_type' => $event->taskType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Release the job back to the queue with a delay
            $this->release(30);
        }
    }

    /**
     * Create a notification for the status change.
     *
     * @param  \App\Events\Warehouse\TaskStatusChangedEvent  $event
     * @return void
     */
    protected function createNotification(TaskStatusChangedEvent $event)
    {
        $message = $event->getNotificationMessage();
        $recipients = $event->getNotificationRecipients();

        // Determine notification type based on status
        $type = $this->getNotificationType($event->newStatus);

        // Create notification data
        $data = [
            'task_id' => $event->task->id,
            'task_type' => $event->taskType,
            'previous_status' => $event->previousStatus,
            'new_status' => $event->newStatus,
            'user_id' => $event->user ? $event->user->id : null,
        ];

        // Dispatch notification event
        event(new NotificationEvent($type, $message, $data, $recipients));
    }

    /**
     * Get the notification type based on the task status.
     *
     * @param  string  $status
     * @return string
     */
    protected function getNotificationType(string $status): string
    {
        switch ($status) {
            case 'completed':
                return 'success';
            case 'cancelled':
            case 'failed':
                return 'error';
            case 'in_progress':
                return 'info';
            case 'on_hold':
                return 'warning';
            default:
                return 'info';
        }
    }

    /**
     * Update task metrics when a task is completed.
     *
     * @param  \App\Events\Warehouse\TaskStatusChangedEvent  $event
     * @return void
     */
    protected function updateTaskMetrics(TaskStatusChangedEvent $event)
    {
        try {
            // Calculate task duration
            if (isset($event->task->started_at) && $event->task->started_at) {
                $startedAt = new \DateTime($event->task->started_at);
                $completedAt = new \DateTime();
                $duration = $startedAt->diff($completedAt);

                Log::info('Task completed', [
                    'task_id' => $event->task->id,
                    'task_type' => $event->taskType,
                    'duration_minutes' => $duration->i + ($duration->h * 60),
                    'user_id' => $event->user ? $event->user->id : null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update task metrics', [
                'task_id' => $event->task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        Log::critical('Task status changed listener failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}