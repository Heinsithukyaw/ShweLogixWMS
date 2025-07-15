<?php

namespace App\Events\Notification;

use App\Events\BaseEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ApprovalStatusEvent extends BaseEvent
{
    /**
     * The entity requiring approval.
     *
     * @var Model
     */
    public $entity;

    /**
     * The entity type.
     *
     * @var string
     */
    public $entityType;

    /**
     * The user who made the approval decision.
     *
     * @var User
     */
    public $approver;

    /**
     * The approval status.
     *
     * @var string
     */
    public $status;

    /**
     * The approval comments.
     *
     * @var string|null
     */
    public $comments;

    /**
     * The user who requested the approval.
     *
     * @var User
     */
    public $requestor;

    /**
     * Create a new event instance.
     *
     * @param Model $entity
     * @param string $entityType
     * @param User $approver
     * @param string $status
     * @param User $requestor
     * @param string|null $comments
     * @return void
     */
    public function __construct(Model $entity, string $entityType, User $approver, string $status, User $requestor, ?string $comments = null)
    {
        parent::__construct();
        $this->entity = $entity;
        $this->entityType = $entityType;
        $this->approver = $approver;
        $this->status = $status;
        $this->requestor = $requestor;
        $this->comments = $comments;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'approval.status_updated';
    }

    /**
     * Get the event payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'entity_id' => $this->entity->id,
            'entity_type' => $this->entityType,
            'approver_id' => $this->approver->id,
            'approver_name' => $this->approver->name,
            'status' => $this->status,
            'comments' => $this->comments,
            'requestor_id' => $this->requestor->id,
            'requestor_name' => $this->requestor->name,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the notification message.
     *
     * @return string
     */
    public function getNotificationMessage(): string
    {
        $statusText = ucfirst($this->status);
        return "{$this->entityType} #{$this->entity->id} has been {$statusText} by {$this->approver->name}";
    }

    /**
     * Get the notification type.
     *
     * @return string
     */
    public function getNotificationType(): string
    {
        return 'approval_' . strtolower($this->status);
    }

    /**
     * Get the notification recipients.
     *
     * @return array
     */
    public function getNotificationRecipients(): array
    {
        // Always notify the requestor
        return [$this->requestor->id];
    }
}