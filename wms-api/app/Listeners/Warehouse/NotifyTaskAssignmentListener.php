<?php

namespace App\Listeners\Warehouse;

use App\Events\Warehouse\TaskCreatedEvent;
use App\Events\Notification\NotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyTaskAssignmentListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  TaskCreatedEvent  $event
     * @return void
     */
    public function handle(TaskCreatedEvent $event)
    {
        try {
            // Only notify if the task is assigned to someone
            if (empty($event->task->assigned_to)) {
                return;
            }
            
            // Get the assigned employee
            $employee = \App\Models\Employee::find($event->task->assigned_to);
            
            if (!$employee) {
                Log::warning('Task assigned to non-existent employee', [
                    'task_id' => $event->task->id,
                    'assigned_to' => $event->task->assigned_to,
                ]);
                return;
            }
            
            // Create notification message
            $taskTypeLabel = ucfirst(str_replace('_', ' ', $event->taskType));
            $message = "New {$taskTypeLabel} task #{$event->task->id} has been assigned to you";
            
            // Dispatch notification event
            event(new NotificationEvent(
                'task_assigned',
                $message,
                [
                    'task_id' => $event->task->id,
                    'task_type' => $event->taskType,
                    'priority' => $event->task->priority ?? 'normal',
                ],
                [$employee->id]
            ));
            
            Log::info('Task assignment notification sent', [
                'task_id' => $event->task->id,
                'employee_id' => $employee->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send task assignment notification', [
                'task_id' => $event->task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}