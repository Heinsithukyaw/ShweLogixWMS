# Event-Driven Architecture in ShweLogixWMS

This document outlines the event-driven architecture implemented in ShweLogixWMS to enable real-time updates, asynchronous processing, and system integration.

## Overview

The event-driven architecture in ShweLogixWMS follows these principles:

1. **Decoupling**: Services communicate through events, reducing direct dependencies
2. **Asynchronous Processing**: Long-running tasks are processed in the background
3. **Real-time Updates**: Frontend receives immediate notifications of changes
4. **Audit Trail**: All events are logged for traceability and debugging
5. **Scalability**: Event processing can be scaled independently of the main application

## Components

### Events

Events are immutable records of something that happened in the system. All events extend the `BaseEvent` class, which provides common functionality:

- Event metadata (timestamp, version, source)
- Serialization methods
- Naming conventions

Events are organized by domain:

- **MasterData**: Events related to product, category, and other master data changes
- **Inbound**: Events related to ASN, GRN, and other inbound processes
- **Inventory**: Events related to inventory changes and allocations
- **Warehouse**: Events related to tasks, locations, and other warehouse operations
- **Notification**: Events related to user notifications

### Listeners

Listeners respond to events and perform actions. They can:

- Update caches
- Send notifications
- Trigger workflows
- Update related data

Listeners are registered in the `EventServiceProvider`.

### Jobs

Jobs handle asynchronous processing of events:

- `ProcessEventJob`: Processes events asynchronously
- `ProcessNotificationJob`: Processes notifications asynchronously

### Services

Services provide a clean API for working with events:

- `EventService`: Dispatches events and provides helper methods
- `EventLogService`: Stores and retrieves events from the database
- `BroadcastService`: Broadcasts events to the frontend

## Event Flow

1. An action occurs in the system (e.g., a product is created)
2. The controller dispatches an event using `EventService`
3. The event is processed synchronously by registered listeners
4. The event is also queued for asynchronous processing
5. The event is stored in the database for auditing
6. The event is broadcast to the frontend for real-time updates

## Broadcasting

Events are broadcast to the frontend using Laravel's broadcasting system. Channels include:

- `events`: General events channel
- `notifications`: General notifications channel
- `notifications.user.{id}`: User-specific notifications
- `inventory`: Inventory-related events
- `warehouse`: Warehouse-related events
- `inbound`: Inbound-related events

## Database Schema

Events and notifications are stored in the database:

### event_logs

- `id`: Primary key
- `event_name`: Name of the event
- `event_source`: Source of the event
- `event_version`: Version of the event
- `payload`: JSON payload of the event
- `event_timestamp`: Timestamp when the event occurred
- `created_at`: Timestamp when the record was created
- `updated_at`: Timestamp when the record was updated

### notifications

- `id`: Primary key
- `type`: Type of notification
- `message`: Notification message
- `data`: JSON data associated with the notification
- `is_read`: Whether the notification has been read
- `read_at`: Timestamp when the notification was read
- `user_id`: User ID the notification is for
- `recipient_type`: Type of recipient (e.g., user, role)
- `recipient_id`: ID of the recipient
- `created_at`: Timestamp when the record was created
- `updated_at`: Timestamp when the record was updated

## Usage Examples

### Dispatching Events

```php
// In a controller or service
use App\Services\EventService;

// Create a product
$product = $this->productRepository->create($request->validated());

// Dispatch an event
EventService::productCreated($product);
```

### Listening for Events

```php
// In a listener
public function handle(ProductCreatedEvent $event)
{
    // Update cache
    Cache::put('product_' . $event->product->id, $event->product, now()->addHours(24));
}
```

### Broadcasting to Frontend

```javascript
// In a Vue component
Echo.channel('inventory')
    .listen('InventoryChangedEvent', (event) => {
        // Update UI
        this.updateInventoryDisplay(event.payload);
    });
```

## Best Practices

1. **Keep Events Small**: Include only necessary data in events
2. **Idempotent Listeners**: Listeners should be idempotent (can be run multiple times without side effects)
3. **Error Handling**: Handle errors gracefully in listeners and jobs
4. **Versioning**: Include version information in events for future compatibility
5. **Documentation**: Document events and their purpose

## Future Enhancements

1. **Event Sourcing**: Implement full event sourcing for critical domains
2. **Message Queue**: Move to a dedicated message queue (RabbitMQ, Kafka)
3. **Event Schema Registry**: Implement a schema registry for event validation
4. **Event Replay**: Add ability to replay events for testing and recovery
5. **Monitoring**: Add monitoring and alerting for event processing