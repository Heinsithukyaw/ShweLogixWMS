<?php

namespace App\Events\Notification;

use App\Events\BaseEvent;

class NotificationEvent extends BaseEvent
{
    /**
     * The notification type.
     *
     * @var string
     */
    public $type;

    /**
     * The notification message.
     *
     * @var string
     */
    public $message;

    /**
     * The notification data.
     *
     * @var array
     */
    public $data;

    /**
     * The notification recipients.
     *
     * @var array
     */
    public $recipients;

    /**
     * Create a new event instance.
     *
     * @param  string  $type
     * @param  string  $message
     * @param  array  $data
     * @param  array  $recipients
     * @return void
     */
    public function __construct(string $type, string $message, array $data = [], array $recipients = [])
    {
        parent::__construct();
        $this->type = $type;
        $this->message = $message;
        $this->data = $data;
        $this->recipients = $recipients;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'notification.sent';
    }

    /**
     * Get the event payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
            'data' => $this->data,
            'recipients' => $this->recipients,
        ];
    }
}