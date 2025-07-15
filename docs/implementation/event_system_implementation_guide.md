# Event System Implementation Guide

This guide explains how to use the implemented event-driven architecture with database transactions and idempotency protection in the ShweLogixWMS system.

## Overview

The event system provides:
- **Database Transactions**: Critical operations are wrapped in database transactions with retry logic
- **Idempotency Protection**: Prevents duplicate processing of events using idempotency keys
- **Event Monitoring**: Real-time monitoring and statistics for event processing
- **Frontend Integration**: React components for monitoring event system performance

## Core Components

### 1. TransactionalEventService

The `TransactionalEventService` provides transaction-wrapped event processing with idempotency protection.

```php
use App\Services\TransactionalEventService;

$service = app(TransactionalEventService::class);

$result = $service->executeWithTransaction(
    'inventory.update_levels',
    ['product_id' => 1, 'quantity' => 100],
    function ($payload) {
        // Your critical operation here
        return updateInventoryLevels($payload);
    },
    'unique-idempotency-key', // Optional
    [
        'max_retries' => 3,
        'retry_delay' => 1000,
        'use_idempotency' => true,
    ]
);
```

### 2. IdempotencyService

The `IdempotencyService` manages idempotency keys to prevent duplicate event processing.

```php
use App\Services\IdempotencyService;

$service = app(IdempotencyService::class);

// Generate idempotency key
$key = $service->generateIdempotencyKey('event.name', $payload, 'source');

// Process with idempotency protection
$result = $service->processWithIdempotency(
    $key,
    'event.name',
    'source',
    $payload,
    function ($payload) {
        // Your processing logic
        return processEvent($payload);
    }
);
```

### 3. UsesTransactionalEvents Trait

The `UsesTransactionalEvents` trait provides convenient methods for different operation types.

```php
use App\Traits\UsesTransactionalEvents;

class InventoryController extends Controller
{
    use UsesTransactionalEvents;

    public function updateInventory(Request $request)
    {
        $result = $this->executeInventoryOperation(
            'update_levels',
            $request->validated(),
            function ($payload) {
                return $this->processInventoryUpdate($payload);
            }
        );

        return response()->json($result);
    }
}
```

## Database Schema

### Event Idempotency Keys Table

```sql
CREATE TABLE event_idempotency_keys (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    idempotency_key VARCHAR(255) UNIQUE NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    event_source VARCHAR(255) NOT NULL,
    event_payload JSON NOT NULL,
    processing_status VARCHAR(50) DEFAULT 'pending',
    processing_result JSON NULL,
    error_message TEXT NULL,
    processed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Event Statistics Tables

The system includes several tables for monitoring event performance:
- `event_statistics` - Event count and timing statistics
- `event_performance_metrics` - Detailed performance metrics
- `event_backlog_alerts` - Backlog monitoring alerts
- `inventory_threshold_alerts` - Inventory-specific alerts

## Usage Examples

### 1. Inventory Operations with Transactions

```php
class InventoryService
{
    use UsesTransactionalEvents;

    public function transferInventory($productId, $fromLocationId, $toLocationId, $quantity)
    {
        $payload = [
            'product_id' => $productId,
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'quantity' => $quantity,
        ];

        return $this->executeInventoryOperation(
            'transfer',
            $payload,
            function ($payload) {
                // This runs within a database transaction
                $this->decreaseInventory($payload['product_id'], $payload['from_location_id'], $payload['quantity']);
                $this->increaseInventory($payload['product_id'], $payload['to_location_id'], $payload['quantity']);
                
                return ['transfer_id' => uniqid()];
            }
        );
    }
}
```

### 2. Event Listeners with Idempotency

```php
class InventoryThresholdListener implements ShouldQueue
{
    use InteractsWithQueue, UsesTransactionalEvents;

    public function handle(InventoryThresholdEvent $event)
    {
        $payload = $event->getPayload();
        
        $idempotencyKey = $this->generateIdempotencyKey(
            'process_inventory_threshold',
            $payload,
            $event->getEventId()
        );

        $result = $this->executeInventoryOperation(
            'process_threshold_alert',
            $payload,
            function ($payload) use ($event) {
                return $this->processThresholdAlert($event, $payload);
            },
            $idempotencyKey
        );

        if ($result['was_duplicate']) {
            Log::info('Duplicate event detected, skipping processing');
            return;
        }

        // Process result...
    }
}
```

### 3. Batch Operations

```php
public function processBatchOrders(array $orders)
{
    $operations = [];
    
    foreach ($orders as $order) {
        $operations[] = [
            'name' => "process_order_{$order['id']}",
            'payload' => $order,
            'callback' => function ($payload) {
                return $this->processOrder($payload);
            },
        ];
    }

    return $this->executeBatchTransactionalOperations($operations);
}
```

## API Endpoints

### Event Monitoring Endpoints

```
GET /api/admin/v1/events/statistics
GET /api/admin/v1/events/performance
GET /api/admin/v1/events/backlog
GET /api/admin/v1/events/logs
GET /api/admin/v1/events/idempotency-statistics
GET /api/admin/v1/events/dashboard-summary
```

### Example API Usage

```javascript
// Get event statistics
const response = await fetch('/api/admin/v1/events/statistics?period=daily');
const data = await response.json();

// Get dashboard summary
const summary = await fetch('/api/admin/v1/events/dashboard-summary');
const summaryData = await summary.json();
```

## Frontend Integration

### EventMonitoringDashboard Component

```jsx
import EventMonitoringDashboard from '../components/dashboard/EventMonitoringDashboard';

function EventMonitoringPage() {
  return (
    <div>
      <h1>Event Monitoring</h1>
      <EventMonitoringDashboard />
    </div>
  );
}
```

### Event Monitoring Service

```javascript
import eventMonitoringService from '../services/eventMonitoringService';

// Get real-time statistics
const unsubscribe = eventMonitoringService.subscribeToStatistics(
  (error, data) => {
    if (error) {
      console.error('Error:', error);
    } else {
      updateDashboard(data);
    }
  },
  { period: 'hourly' },
  30000 // 30 second intervals
);

// Stop subscription
unsubscribe();
```

## Configuration

### Environment Variables

```env
# Event system configuration
EVENT_MONITORING_ENABLED=true
EVENT_IDEMPOTENCY_TTL_HOURS=24
EVENT_RETRY_MAX_ATTEMPTS=3
EVENT_RETRY_DELAY_MS=1000

# Queue configuration
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Scheduled Tasks

The system includes several scheduled tasks:

```php
// In app/Console/Kernel.php
$schedule->command('wms:monitor-events')->everyFifteenMinutes();
$schedule->command('wms:monitor-inventory')->hourly();
$schedule->command('wms:cleanup-idempotency-keys')->daily();
```

## Best Practices

### 1. Idempotency Key Generation

```php
// Good: Include all relevant data
$key = $this->generateIdempotencyKey(
    'inventory.transfer',
    [
        'product_id' => $productId,
        'from_location' => $fromLocation,
        'to_location' => $toLocation,
        'quantity' => $quantity,
    ],
    $requestId
);

// Bad: Missing important context
$key = hash('sha256', $productId . $quantity);
```

### 2. Transaction Boundaries

```php
// Good: Keep transactions focused
$this->executeInventoryOperation(
    'update_stock',
    $payload,
    function ($payload) {
        // Only inventory-related operations
        $this->updateStock($payload);
        $this->logInventoryMovement($payload);
        return $result;
    }
);

// Bad: Mixing concerns in transaction
$this->executeInventoryOperation(
    'update_stock',
    $payload,
    function ($payload) {
        $this->updateStock($payload);
        $this->sendEmail($payload); // Should be outside transaction
        $this->updateAnalytics($payload); // Should be outside transaction
        return $result;
    }
);
```

### 3. Error Handling

```php
try {
    $result = $this->executeInventoryOperation(
        'critical_operation',
        $payload,
        function ($payload) {
            return $this->processCriticalOperation($payload);
        }
    );
} catch (\InvalidArgumentException $e) {
    // Don't retry validation errors
    return $this->handleValidationError($e);
} catch (\Exception $e) {
    // Log and handle other errors
    Log::error('Critical operation failed', [
        'error' => $e->getMessage(),
        'payload' => $payload,
    ]);
    
    throw $e;
}
```

## Monitoring and Debugging

### 1. Event Logs

```bash
# View event processing logs
tail -f storage/logs/event-monitoring.log

# View idempotency cleanup logs
tail -f storage/logs/idempotency-cleanup.log
```

### 2. Database Queries

```sql
-- Check idempotency key statistics
SELECT 
    processing_status,
    COUNT(*) as count,
    AVG(TIMESTAMPDIFF(SECOND, created_at, processed_at)) as avg_processing_time
FROM event_idempotency_keys 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY processing_status;

-- Find slow events
SELECT 
    event_name,
    AVG(processing_time_ms) as avg_time,
    MAX(processing_time_ms) as max_time,
    COUNT(*) as count
FROM event_performance_metrics 
WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY event_name
ORDER BY avg_time DESC;
```

### 3. Artisan Commands

```bash
# Clean up expired idempotency keys
php artisan wms:cleanup-idempotency-keys

# Monitor event performance
php artisan wms:monitor-events

# Check inventory thresholds
php artisan wms:monitor-inventory
```

## Troubleshooting

### Common Issues

1. **Duplicate Processing**: Check idempotency key generation logic
2. **Transaction Deadlocks**: Review transaction boundaries and ordering
3. **Memory Issues**: Monitor queue worker memory usage
4. **Performance**: Check event processing times and optimize slow operations

### Debug Mode

Enable debug logging for detailed event processing information:

```php
// In config/logging.php
'channels' => [
    'events' => [
        'driver' => 'daily',
        'path' => storage_path('logs/events.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

This implementation provides a robust, scalable event system with proper transaction handling and idempotency protection for the ShweLogixWMS application.