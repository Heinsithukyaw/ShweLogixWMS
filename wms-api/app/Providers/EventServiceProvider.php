<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Master Data Events
use App\Events\MasterData\ProductCreatedEvent;
use App\Events\MasterData\ProductUpdatedEvent;
use App\Listeners\MasterData\UpdateProductCacheListener;

// Inbound Events
use App\Events\Inbound\ASNReceivedEvent;
use App\Events\Inbound\GoodsReceivedEvent;
use App\Listeners\Inbound\SendASNNotificationListener;

// Inventory Events
use App\Events\Inventory\InventoryChangedEvent;
use App\Events\Inventory\InventoryAllocatedEvent;
use App\Events\Inventory\InventoryThresholdEvent;
use App\Listeners\Inventory\UpdateInventoryCacheListener;
use App\Listeners\Inventory\InventoryThresholdListener;

// Warehouse Events
use App\Events\Warehouse\TaskCreatedEvent;
use App\Events\Warehouse\TaskCompletedEvent;
use App\Events\Warehouse\TaskStatusChangedEvent;
use App\Listeners\Warehouse\NotifyTaskAssignmentListener;
use App\Listeners\Warehouse\TaskStatusChangedListener;

// Notification Events
use App\Events\Notification\NotificationEvent;
use App\Events\Notification\ThresholdAlertEvent;
use App\Listeners\Notification\ProcessNotificationListener;
use App\Listeners\Notification\ThresholdAlertListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Master Data Events
        ProductCreatedEvent::class => [
            UpdateProductCacheListener::class,
        ],
        ProductUpdatedEvent::class => [
            UpdateProductCacheListener::class,
        ],
        
        // Inbound Events
        ASNReceivedEvent::class => [
            SendASNNotificationListener::class,
        ],
        GoodsReceivedEvent::class => [
            // Add listeners for goods received events
        ],
        
        // Inventory Events
        InventoryChangedEvent::class => [
            UpdateInventoryCacheListener::class,
        ],
        InventoryAllocatedEvent::class => [
            // Add listeners for inventory allocation events
        ],
        InventoryThresholdEvent::class => [
            InventoryThresholdListener::class,
        ],
        
        // Warehouse Events
        TaskCreatedEvent::class => [
            NotifyTaskAssignmentListener::class,
        ],
        TaskCompletedEvent::class => [
            // Add listeners for task completion events
        ],
        TaskStatusChangedEvent::class => [
            TaskStatusChangedListener::class,
        ],
        
        // Notification Events
        NotificationEvent::class => [
            ProcessNotificationListener::class,
        ],
        ThresholdAlertEvent::class => [
            ThresholdAlertListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register the StoreEventListener for all BaseEvent instances
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\BaseEvent::class,
            \App\Listeners\StoreEventListener::class
        );
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
