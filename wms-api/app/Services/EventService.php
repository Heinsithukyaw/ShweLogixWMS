<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EventService
{
    /**
     * Dispatch an event.
     *
     * @param  mixed  $event
     * @param  bool  $async
     * @return void
     */
    public static function dispatch($event, $async = true)
    {
        try {
            // Dispatch the event synchronously
            event($event);
            
            // If async is enabled, also dispatch the event to the queue
            if ($async && $event instanceof \App\Events\BaseEvent) {
                \App\Jobs\ProcessEventJob::dispatch($event);
            }
            
            // Log the event
            if (method_exists($event, 'getName') && method_exists($event, 'getPayload')) {
                Log::info('Event dispatched', [
                    'event' => $event->getName(),
                    'payload' => $event->getPayload(),
                    'async' => $async,
                ]);
            } else {
                Log::info('Event dispatched', [
                    'event' => get_class($event),
                    'async' => $async,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to dispatch event', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    /**
     * Dispatch a product created event.
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    public static function productCreated($product)
    {
        self::dispatch(new \App\Events\MasterData\ProductCreatedEvent($product));
    }
    
    /**
     * Dispatch a product updated event.
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    public static function productUpdated($product)
    {
        self::dispatch(new \App\Events\MasterData\ProductUpdatedEvent($product));
    }
    
    /**
     * Dispatch an ASN received event.
     *
     * @param  \App\Models\AdvancedShippingNotice  $asn
     * @return void
     */
    public static function asnReceived($asn)
    {
        self::dispatch(new \App\Events\Inbound\ASNReceivedEvent($asn));
    }
    
    /**
     * Dispatch a goods received event.
     *
     * @param  \App\Models\GoodReceivedNote  $grn
     * @return void
     */
    public static function goodsReceived($grn)
    {
        self::dispatch(new \App\Events\Inbound\GoodsReceivedEvent($grn));
    }
    
    /**
     * Dispatch an inventory changed event.
     *
     * @param  \App\Models\ProductInventory  $inventory
     * @param  string  $changeType
     * @param  int  $previousQuantity
     * @return void
     */
    public static function inventoryChanged($inventory, $changeType, $previousQuantity)
    {
        self::dispatch(new \App\Events\Inventory\InventoryChangedEvent($inventory, $changeType, $previousQuantity));
    }
    
    /**
     * Dispatch an inventory allocated event.
     *
     * @param  \App\Models\ProductInventory  $inventory
     * @param  int  $orderId
     * @param  int  $allocatedQuantity
     * @return void
     */
    public static function inventoryAllocated($inventory, $orderId, $allocatedQuantity)
    {
        self::dispatch(new \App\Events\Inventory\InventoryAllocatedEvent($inventory, $orderId, $allocatedQuantity));
    }
    
    /**
     * Dispatch a task created event.
     *
     * @param  mixed  $task
     * @param  string  $taskType
     * @return void
     */
    public static function taskCreated($task, $taskType)
    {
        self::dispatch(new \App\Events\Warehouse\TaskCreatedEvent($task, $taskType));
    }
    
    /**
     * Dispatch a task completed event.
     *
     * @param  mixed  $task
     * @param  string  $taskType
     * @return void
     */
    public static function taskCompleted($task, $taskType)
    {
        self::dispatch(new \App\Events\Warehouse\TaskCompletedEvent($task, $taskType));
    }
    
    /**
     * Dispatch a notification event.
     *
     * @param  string  $type
     * @param  string  $message
     * @param  array  $data
     * @param  array  $recipients
     * @return void
     */
    public static function sendNotification($type, $message, $data = [], $recipients = [])
    {
        self::dispatch(new \App\Events\Notification\NotificationEvent($type, $message, $data, $recipients));
    }
}