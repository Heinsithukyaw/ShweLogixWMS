<?php

namespace App\Listeners\Notification;

use App\Events\Notification\ThresholdAlertEvent;
use App\Jobs\ProcessNotificationJob;
use App\Models\User;
use App\Services\NotificationRoutingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ThresholdAlertListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The notification routing service.
     *
     * @var NotificationRoutingService
     */
    protected $notificationRoutingService;

    /**
     * Create the event listener.
     *
     * @param NotificationRoutingService $notificationRoutingService
     * @return void
     */
    public function __construct(NotificationRoutingService $notificationRoutingService)
    {
        $this->notificationRoutingService = $notificationRoutingService;
    }

    /**
     * Handle the event.
     *
     * @param  ThresholdAlertEvent  $event
     * @return void
     */
    public function handle(ThresholdAlertEvent $event)
    {
        try {
            // Determine who should receive this threshold alert
            $recipients = $this->determineRecipients($event);
            
            // Process the notification
            ProcessNotificationJob::dispatch(
                $event->getNotificationType(),
                $event->getNotificationMessage(),
                $event->getPayload(),
                $recipients
            );
            
            // Log the threshold alert
            Log::info('Threshold alert processed', [
                'threshold_type' => $event->thresholdType,
                'entity_type' => $event->entityType,
                'entity_id' => $event->entityId,
                'severity' => $event->severity,
                'recipients' => count($recipients),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process threshold alert', [
                'threshold_type' => $event->thresholdType,
                'entity_type' => $event->entityType,
                'entity_id' => $event->entityId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine who should receive this threshold alert.
     *
     * @param  ThresholdAlertEvent  $event
     * @return array
     */
    protected function determineRecipients(ThresholdAlertEvent $event)
    {
        // Use the notification routing service to determine recipients
        // This would typically be based on the threshold type, entity type, severity, etc.
        
        // For now, we'll implement a simple routing logic
        $recipients = [];
        
        // Get users based on threshold type and severity
        switch ($event->thresholdType) {
            case 'inventory':
                // Inventory managers and product owners
                $recipients = $this->notificationRoutingService->getUsersByRole(['inventory_manager', 'product_owner']);
                break;
                
            case 'capacity':
                // Warehouse managers and location managers
                $recipients = $this->notificationRoutingService->getUsersByRole(['warehouse_manager', 'location_manager']);
                break;
                
            case 'performance':
                // Operations managers and supervisors
                $recipients = $this->notificationRoutingService->getUsersByRole(['operations_manager', 'supervisor']);
                break;
                
            default:
                // Default to system administrators
                $recipients = $this->notificationRoutingService->getUsersByRole(['admin']);
                break;
        }
        
        // For critical severity, always include administrators
        if ($event->severity === 'critical') {
            $adminRecipients = $this->notificationRoutingService->getUsersByRole(['admin']);
            $recipients = array_merge($recipients, $adminRecipients);
        }
        
        // Remove duplicates
        $recipients = array_unique($recipients);
        
        return $recipients;
    }
}