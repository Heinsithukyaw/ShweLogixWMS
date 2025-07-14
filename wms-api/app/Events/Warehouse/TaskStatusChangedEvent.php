<?php

namespace App\Events\Warehouse;

use App\Events\BaseEvent;

class TaskStatusChangedEvent extends BaseEvent
{
    /**
     * The task.
     *
     * @var mixed
     */
    public $task;

    /**
     * The task type.
     *
     * @var string
     */
    public $taskType;

    /**
     * The previous status.
     *
     * @var string
     */
    public $previousStatus;

    /**
     * The new status.
     *
     * @var string
     */
    public $newStatus;

    /**
     * The user who changed the status.
     *
     * @var \App\Models\User|null
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  mixed  $task
     * @param  string  $taskType
     * @param  string  $previousStatus
     * @param  string  $newStatus
     * @param  \App\Models\User|null  $user
     * @return void
     */
    public function __construct($task, string $taskType, string $previousStatus, string $newStatus, $user = null)
    {
        parent::__construct();
        $this->task = $task;
        $this->taskType = $taskType;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->user = $user;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'task.status.changed';
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
            'task_type' => $this->taskType,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'user_id' => $this->user ? $this->user->id : null,
            'user_name' => $this->user ? $this->user->name : null,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the notification message.
     *
     * @return string
     */
    public function getNotificationMessage(): string
    {
        $taskId = $this->task->id;
        $taskType = ucfirst(str_replace('_', ' ', $this->taskType));
        $newStatus = ucfirst(str_replace('_', ' ', $this->newStatus));
        $userName = $this->user ? $this->user->name : 'System';

        return "{$taskType} #{$taskId} status changed to {$newStatus} by {$userName}";
    }

    /**
     * Get the notification recipients.
     *
     * @return array
     */
    public function getNotificationRecipients(): array
    {
        $recipients = [];

        // Add the task assignee if available
        if (isset($this->task->user_id)) {
            $recipients[] = $this->task->user_id;
        }

        // Add the task creator if available and different from assignee
        if (isset($this->task->created_by) && !in_array($this->task->created_by, $recipients)) {
            $recipients[] = $this->task->created_by;
        }

        return $recipients;
    }
}