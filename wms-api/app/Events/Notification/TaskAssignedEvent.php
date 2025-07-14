<?php

namespace App\Events\Notification;

use App\Events\BaseEvent;
use App\Models\Task;
use App\Models\User;

class TaskAssignedEvent extends BaseEvent
{
    /**
     * The task that was assigned.
     *
     * @var Task
     */
    public $task;

    /**
     * The user the task was assigned to.
     *
     * @var User
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param Task $task
     * @param User $user
     * @return void
     */
    public function __construct(Task $task, User $user)
    {
        parent::__construct();
        $this->task = $task;
        $this->user = $user;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'task.assigned';
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
            'task_priority' => $this->task->priority,
            'task_due_date' => $this->task->due_date,
            'task_description' => $this->task->description,
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
        ];
    }

    /**
     * Get the notification message.
     *
     * @return string
     */
    public function getNotificationMessage(): string
    {
        return "New {$this->task->type} task assigned: {$this->task->description}";
    }

    /**
     * Get the notification type.
     *
     * @return string
     */
    public function getNotificationType(): string
    {
        return 'task_assigned';
    }

    /**
     * Get the notification recipients.
     *
     * @return array
     */
    public function getNotificationRecipients(): array
    {
        return [$this->user->id];
    }
}