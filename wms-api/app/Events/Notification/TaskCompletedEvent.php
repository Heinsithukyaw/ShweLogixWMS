<?php

namespace App\Events\Notification;

use App\Events\BaseEvent;
use App\Models\Task;
use App\Models\User;

class TaskCompletedEvent extends BaseEvent
{
    /**
     * The task that was completed.
     *
     * @var Task
     */
    public $task;

    /**
     * The user who completed the task.
     *
     * @var User
     */
    public $user;

    /**
     * The supervisor to notify.
     *
     * @var User|null
     */
    public $supervisor;

    /**
     * Create a new event instance.
     *
     * @param Task $task
     * @param User $user
     * @param User|null $supervisor
     * @return void
     */
    public function __construct(Task $task, User $user, ?User $supervisor = null)
    {
        parent::__construct();
        $this->task = $task;
        $this->user = $user;
        $this->supervisor = $supervisor;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'task.completed';
    }

    /**
     * Get the event payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'task_id' => $this->task->id,
            'task_type' => $this->task->type,
            'task_description' => $this->task->description,
            'completion_time' => now()->toIso8601String(),
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'supervisor_id' => $this->supervisor ? $this->supervisor->id : null,
        ];
    }

    /**
     * Get the notification message.
     *
     * @return string
     */
    public function getNotificationMessage(): string
    {
        return "Task completed: {$this->task->description} by {$this->user->name}";
    }

    /**
     * Get the notification type.
     *
     * @return string
     */
    public function getNotificationType(): string
    {
        return 'task_completed';
    }

    /**
     * Get the notification recipients.
     *
     * @return array
     */
    public function getNotificationRecipients(): array
    {
        $recipients = [];
        
        if ($this->supervisor) {
            $recipients[] = $this->supervisor->id;
        }
        
        // Add any other stakeholders who need to be notified
        // For example, task creator or department manager
        
        return $recipients;
    }
}