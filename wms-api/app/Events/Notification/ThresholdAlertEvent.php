<?php

namespace App\Events\Notification;

use App\Events\BaseEvent;

class ThresholdAlertEvent extends BaseEvent
{
    /**
     * The threshold type.
     *
     * @var string
     */
    public $thresholdType;

    /**
     * The entity that triggered the threshold.
     *
     * @var string
     */
    public $entityType;

    /**
     * The entity ID that triggered the threshold.
     *
     * @var int|string
     */
    public $entityId;

    /**
     * The current value.
     *
     * @var float|int
     */
    public $currentValue;

    /**
     * The threshold value.
     *
     * @var float|int
     */
    public $thresholdValue;

    /**
     * The comparison operator.
     *
     * @var string
     */
    public $operator;

    /**
     * The severity level.
     *
     * @var string
     */
    public $severity;

    /**
     * Additional data.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param string $thresholdType
     * @param string $entityType
     * @param int|string $entityId
     * @param float|int $currentValue
     * @param float|int $thresholdValue
     * @param string $operator
     * @param string $severity
     * @param array $data
     * @return void
     */
    public function __construct(
        string $thresholdType,
        string $entityType,
        $entityId,
        $currentValue,
        $thresholdValue,
        string $operator = '<',
        string $severity = 'warning',
        array $data = []
    ) {
        parent::__construct();
        $this->thresholdType = $thresholdType;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->currentValue = $currentValue;
        $this->thresholdValue = $thresholdValue;
        $this->operator = $operator;
        $this->severity = $severity;
        $this->data = $data;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'threshold.alert';
    }

    /**
     * Get the event payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'threshold_type' => $this->thresholdType,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'current_value' => $this->currentValue,
            'threshold_value' => $this->thresholdValue,
            'operator' => $this->operator,
            'severity' => $this->severity,
            'data' => $this->data,
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
        $operatorText = $this->getOperatorText();
        return "{$this->thresholdType} threshold {$operatorText} for {$this->entityType} #{$this->entityId}: {$this->currentValue} {$this->operator} {$this->thresholdValue}";
    }

    /**
     * Get the notification type.
     *
     * @return string
     */
    public function getNotificationType(): string
    {
        return $this->severity;
    }

    /**
     * Get the notification recipients.
     *
     * @return array
     */
    public function getNotificationRecipients(): array
    {
        // This would typically be resolved by a service that determines who should receive threshold alerts
        // For now, we'll return an empty array as the actual implementation would depend on your notification routing system
        return [];
    }

    /**
     * Get a human-readable text for the operator.
     *
     * @return string
     */
    protected function getOperatorText(): string
    {
        switch ($this->operator) {
            case '<':
                return 'below';
            case '<=':
                return 'at or below';
            case '>':
                return 'above';
            case '>=':
                return 'at or above';
            case '=':
            case '==':
                return 'equal to';
            case '!=':
                return 'not equal to';
            default:
                return $this->operator;
        }
    }

    /**
     * Create an inventory threshold alert.
     *
     * @param string $productId
     * @param float|int $currentQuantity
     * @param float|int $thresholdQuantity
     * @param string $operator
     * @param string $severity
     * @param array $data
     * @return static
     */
    public static function inventory($productId, $currentQuantity, $thresholdQuantity, $operator = '<', $severity = 'warning', $data = [])
    {
        return new static('inventory', 'product', $productId, $currentQuantity, $thresholdQuantity, $operator, $severity, $data);
    }

    /**
     * Create a capacity threshold alert.
     *
     * @param string $locationId
     * @param float|int $currentCapacity
     * @param float|int $thresholdCapacity
     * @param string $operator
     * @param string $severity
     * @param array $data
     * @return static
     */
    public static function capacity($locationId, $currentCapacity, $thresholdCapacity, $operator = '>', $severity = 'warning', $data = [])
    {
        return new static('capacity', 'location', $locationId, $currentCapacity, $thresholdCapacity, $operator, $severity, $data);
    }

    /**
     * Create a performance threshold alert.
     *
     * @param string $metricType
     * @param string $entityType
     * @param string $entityId
     * @param float|int $currentValue
     * @param float|int $thresholdValue
     * @param string $operator
     * @param string $severity
     * @param array $data
     * @return static
     */
    public static function performance($metricType, $entityType, $entityId, $currentValue, $thresholdValue, $operator = '<', $severity = 'warning', $data = [])
    {
        $data['metric_type'] = $metricType;
        return new static('performance', $entityType, $entityId, $currentValue, $thresholdValue, $operator, $severity, $data);
    }
}