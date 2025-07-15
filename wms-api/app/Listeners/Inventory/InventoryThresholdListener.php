<?php

namespace App\Listeners\Inventory;

use App\Events\Inventory\InventoryThresholdEvent;
use App\Events\Notification\ThresholdAlertEvent;
use App\Models\InventoryThresholdAlert;
use App\Services\NotificationRoutingService;
use App\Traits\UsesTransactionalEvents;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InventoryThresholdListener implements ShouldQueue
{
    use InteractsWithQueue, UsesTransactionalEvents;

    /**
     * The notification routing service.
     *
     * @var \App\Services\NotificationRoutingService
     */
    protected $notificationRoutingService;

    /**
     * Create the event listener.
     *
     * @param  \App\Services\NotificationRoutingService  $notificationRoutingService
     * @return void
     */
    public function __construct(NotificationRoutingService $notificationRoutingService)
    {
        $this->notificationRoutingService = $notificationRoutingService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\Inventory\InventoryThresholdEvent  $event
     * @return void
     */
    public function handle(InventoryThresholdEvent $event)
    {
        $payload = [
            'threshold_type' => $event->thresholdType,
            'product_id' => $event->product->id,
            'location_id' => $event->location->id,
            'threshold_value' => $event->thresholdValue,
            'current_value' => $event->currentValue,
            'severity' => $event->severity,
        ];

        // Generate idempotency key for this specific threshold event
        $idempotencyKey = $this->generateIdempotencyKey(
            'process_inventory_threshold',
            $payload,
            $event->getEventId()
        );

        try {
            $result = $this->executeInventoryOperation(
                'process_threshold_alert',
                $payload,
                function ($payload) use ($event) {
                    return $this->processThresholdAlert($event, $payload);
                },
                $idempotencyKey
            );

            if ($result['was_duplicate']) {
                Log::info('Duplicate inventory threshold event detected, skipping processing', [
                    'idempotency_key' => $idempotencyKey,
                    'threshold_type' => $event->thresholdType,
                ]);
                return;
            }

            Log::info('Inventory threshold event processed successfully', [
                'threshold_type' => $event->thresholdType,
                'product_id' => $event->product->id,
                'location_id' => $event->location->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process inventory threshold event', [
                'threshold_type' => $event->thresholdType,
                'product_id' => $event->product->id,
                'location_id' => $event->location->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Release the job back to the queue with a delay
            $this->release(30);
        }
    }

    /**
     * Process the threshold alert within a transaction.
     *
     * @param  \App\Events\Inventory\InventoryThresholdEvent  $event
     * @param  array  $payload
     * @return array
     */
    protected function processThresholdAlert(InventoryThresholdEvent $event, array $payload): array
    {
        // Create or update inventory threshold alert
        $alert = $this->createOrUpdateAlert($event);

        // Generate notification
        $this->generateNotification($event);

        return [
            'alert_id' => $alert->id,
            'alert_created' => $alert->wasRecentlyCreated,
            'notification_sent' => true,
        ];
    }

    /**
     * Create or update inventory threshold alert.
     *
     * @param  \App\Events\Inventory\InventoryThresholdEvent  $event
     * @return \App\Models\InventoryThresholdAlert
     */
    protected function createOrUpdateAlert(InventoryThresholdEvent $event): InventoryThresholdAlert
    {
        // Check if there's an existing active alert for this product, location, and threshold type
        $alert = InventoryThresholdAlert::active()
            ->forProduct($event->product->id)
            ->forLocation($event->location->id)
            ->ofType($event->thresholdType)
            ->first();

        if ($alert) {
            // Update existing alert
            $alert->update([
                'threshold_value' => $event->thresholdValue,
                'current_value' => $event->currentValue,
                'severity' => $event->severity,
            ]);
        } else {
            // Create new alert
            $alert = InventoryThresholdAlert::create([
                'product_id' => $event->product->id,
                'location_id' => $event->location->id,
                'threshold_type' => $event->thresholdType,
                'threshold_value' => $event->thresholdValue,
                'current_value' => $event->currentValue,
                'severity' => $event->severity,
                'is_resolved' => false,
                'detected_at' => now(),
            ]);
        }

        return $alert;
    }

    /**
     * Generate notification for the threshold event.
     *
     * @param  \App\Events\Inventory\InventoryThresholdEvent  $event
     * @return void
     */
    protected function generateNotification(InventoryThresholdEvent $event): void
    {
        $message = $this->buildNotificationMessage($event);
        $data = $event->getPayload();

        if ($event->severity === 'critical') {
            event(ThresholdAlertEvent::critical($message, 'inventory_threshold', 'inventory', $data));
        } else {
            event(ThresholdAlertEvent::warning($message, 'inventory_threshold', 'inventory', $data));
        }
    }

    /**
     * Build notification message based on threshold type.
     *
     * @param  \App\Events\Inventory\InventoryThresholdEvent  $event
     * @return string
     */
    protected function buildNotificationMessage(InventoryThresholdEvent $event): string
    {
        $productName = $event->product->product_name;
        $locationName = $event->location->location_name;
        $currentValue = $event->currentValue;
        $thresholdValue = $event->thresholdValue;

        switch ($event->thresholdType) {
            case 'low_stock':
                return "Low stock alert: {$productName} at {$locationName} has {$currentValue} units (threshold: {$thresholdValue})";

            case 'high_stock':
                return "High stock alert: {$productName} at {$locationName} has {$currentValue} units (threshold: {$thresholdValue})";

            case 'expiring_soon':
                return "Expiring inventory alert: {$productName} at {$locationName} has {$currentValue} units expiring within {$thresholdValue} days";

            default:
                return "Inventory threshold alert: {$productName} at {$locationName}";
        }
    }

    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        Log::critical('Inventory threshold listener failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}