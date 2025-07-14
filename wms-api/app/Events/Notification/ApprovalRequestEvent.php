<?php

namespace App\Events\Notification;

use App\Events\BaseEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequestEvent extends BaseEvent
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
     * The user requesting approval.
     *
     * @var User
     */
    public $requestor;

    /**
     * The users who can approve.
     *
     * @var array
     */
    public $approvers;

    /**
     * Create a new event instance.
     *
     * @param Model $entity
     * @param string $entityType
     * @param User $requestor
     * @param array $approvers
     * @return void
     */
    public function __construct(Model $entity, string $entityType, User $requestor, array $approvers)
    {
        parent::__construct();
        $this->entity = $entity;
        $this->entityType = $entityType;
        $this->requestor = $requestor;
        $this->approvers = $approvers;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'approval.requested';
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
            'requestor_id' => $this->requestor->id,
            'requestor_name' => $this->requestor->name,
            'approvers' => collect($this->approvers)->map(function ($approver) {
                return [
                    'id' => $approver->id,
                    'name' => $approver->name,
                ];
            })->toArray(),
            'requested_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the notification message.
     *
     * @return string
     */
    public function getNotificationMessage(): string
    {
        return "Approval requested for {$this->entityType} #{$this->entity->id} by {$this->requestor->name}";
    }

    /**
     * Get the notification type.
     *
     * @return string
     */
    public function getNotificationType(): string
    {
        return 'approval_requested';
    }

    /**
     * Get the notification recipients.
     *
     * @return array
     */
    public function getNotificationRecipients(): array
    {
        return collect($this->approvers)->pluck('id')->toArray();
    }
}