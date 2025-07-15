<?php

namespace App\Events\Notification;

use App\Events\BaseEvent;

class SystemAlertEvent extends BaseEvent
{
    /**
     * The alert type.
     *
     * @var string
     */
    public $alertType;

    /**
     * The alert message.
     *
     * @var string
     */
    public $message;

    /**
     * The alert severity.
     *
     * @var string
     */
    public $severity;

    /**
     * The alert source.
     *
     * @var string
     */
    public $source;

    /**
     * Additional alert data.
     *
     * @var array
     */
    public $data;

    /**
     * The user roles that should receive this alert.
     *
     * @var array
     */
    public $targetRoles;

    /**
     * Create a new event instance.
     *
     * @param string $alertType
     * @param string $message
     * @param string $severity
     * @param string $source
     * @param array $data
     * @param array $targetRoles
     * @return void
     */
    public function __construct(
        string $alertType,
        string $message,
        string $severity = 'info',
        string $source = 'system',
        array $data = [],
        array $targetRoles = ['admin']
    ) {
        parent::__construct();
        $this->alertType = $alertType;
        $this->message = $message;
        $this->severity = $severity;
        $this->source = $source;
        $this->data = $data;
        $this->targetRoles = $targetRoles;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'system.alert';
    }

    /**
     * Get the event payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'alert_type' => $this->alertType,
            'message' => $this->message,
            'severity' => $this->severity,
            'source' => $this->source,
            'data' => $this->data,
            'target_roles' => $this->targetRoles,
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
        return $this->message;
    }

    /**
     * Get the notification type.
     *
     * @return string
     */
    public function getNotificationType(): string
    {
        return $this->severity; // Using severity as the notification type (info, warning, error, etc.)
    }

    /**
     * Get the notification recipients.
     *
     * @return array
     */
    public function getNotificationRecipients(): array
    {
        // This would typically be resolved by a service that looks up users with the specified roles
        // For now, we'll return an empty array as the actual implementation would depend on your user/role system
        return [];
    }

    /**
     * Create an info alert.
     *
     * @param string $message
     * @param string $alertType
     * @param string $source
     * @param array $data
     * @param array $targetRoles
     * @return static
     */
    public static function info(string $message, string $alertType = 'info', string $source = 'system', array $data = [], array $targetRoles = ['admin'])
    {
        return new static($alertType, $message, 'info', $source, $data, $targetRoles);
    }

    /**
     * Create a warning alert.
     *
     * @param string $message
     * @param string $alertType
     * @param string $source
     * @param array $data
     * @param array $targetRoles
     * @return static
     */
    public static function warning(string $message, string $alertType = 'warning', string $source = 'system', array $data = [], array $targetRoles = ['admin'])
    {
        return new static($alertType, $message, 'warning', $source, $data, $targetRoles);
    }

    /**
     * Create an error alert.
     *
     * @param string $message
     * @param string $alertType
     * @param string $source
     * @param array $data
     * @param array $targetRoles
     * @return static
     */
    public static function error(string $message, string $alertType = 'error', string $source = 'system', array $data = [], array $targetRoles = ['admin'])
    {
        return new static($alertType, $message, 'error', $source, $data, $targetRoles);
    }

    /**
     * Create a critical alert.
     *
     * @param string $message
     * @param string $alertType
     * @param string $source
     * @param array $data
     * @param array $targetRoles
     * @return static
     */
    public static function critical(string $message, string $alertType = 'critical', string $source = 'system', array $data = [], array $targetRoles = ['admin'])
    {
        return new static($alertType, $message, 'critical', $source, $data, $targetRoles);
    }
}