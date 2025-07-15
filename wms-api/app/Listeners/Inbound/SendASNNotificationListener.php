<?php

namespace App\Listeners\Inbound;

use App\Events\Inbound\ASNReceivedEvent;
use App\Events\Notification\NotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendASNNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  ASNReceivedEvent  $event
     * @return void
     */
    public function handle(ASNReceivedEvent $event)
    {
        try {
            // Get the supplier information
            $supplier = $event->asn->supplier;
            
            // Create notification message
            $message = "New ASN #{$event->asn->asn_number} received from {$supplier->business_party_name}";
            
            // Determine recipients (e.g., warehouse managers, receiving staff)
            $recipients = $this->getRecipients();
            
            // Dispatch notification event
            event(new NotificationEvent(
                'asn_received',
                $message,
                [
                    'asn_id' => $event->asn->id,
                    'asn_number' => $event->asn->asn_number,
                    'supplier_id' => $event->asn->supplier_id,
                    'expected_arrival_date' => $event->asn->expected_arrival_date,
                ],
                $recipients
            ));
            
            Log::info('ASN notification sent', [
                'asn_id' => $event->asn->id,
                'recipients' => $recipients,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send ASN notification', [
                'asn_id' => $event->asn->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Get the notification recipients.
     *
     * @return array
     */
    private function getRecipients()
    {
        // In a real implementation, this would query the database for users with appropriate roles
        // For now, we'll return a placeholder
        return ['warehouse_managers', 'receiving_staff'];
    }
}