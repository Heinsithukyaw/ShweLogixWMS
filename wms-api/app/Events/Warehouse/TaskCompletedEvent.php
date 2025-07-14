<?php

namespace App\Events\Warehouse;

use App\Events\BaseEvent;

class TaskCompletedEvent extends BaseEvent
{
    /**
     * The task instance.
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
     * Create a new event instance.
     *
     * @param  mixed  $task
     * @param  string  $taskType
     * @return void
     */
    public function __construct($task, string $taskType)
    {
        parent::__construct();
        $this->task = $task;
        $this->taskType = $taskType;
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
            'id' => $this->task->id,
            'task_type' => $this->taskType,
            'status' => $this->task->status,
            'completed_by' => $this->task->completed_by ?? null,
            'completed_at' => $this->task->completed_at ?? now()->toIso8601String(),
            'duration' => $this->task->duration ?? null,
        ];
    }
}