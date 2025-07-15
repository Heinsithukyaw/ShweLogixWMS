<?php

namespace App\Events\Inventory;

use App\Events\BaseEvent;
use App\Models\ProductInventory;

class InventoryThresholdEvent extends BaseEvent
{
    /**
     * The inventory record.
     *
     * @var \App\Models\ProductInventory
     */
    public $inventory;

    /**
     * The threshold type.
     *
     * @var string
     */
    public $thresholdType;

    /**
     * The threshold value.
     *
     * @var int
     */
    public $thresholdValue;

    /**
     * The current value.
     *
     * @var int
     */
    public $currentValue;

    /**
     * The severity level.
     *
     * @var string
     */
    public $severity;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\ProductInventory  $inventory
     * @param  string  $thresholdType
     * @param  int  $thresholdValue
     * @param  int  $currentValue
     * @param  string  $severity
     * @return void
     */
    public function __construct(
        ProductInventory $inventory, 
        string $thresholdType, 
        int $thresholdValue, 
        int $currentValue, 
        string $severity = 'warning'
    ) {
        parent::__construct();
        $this->inventory = $inventory;
        $this->thresholdType = $thresholdType;
        $this->thresholdValue = $thresholdValue;
        $this->currentValue = $currentValue;
        $this->severity = $severity;
    }

    /**
     * Create a low stock threshold event.
     *
     * @param  \App\Models\ProductInventory  $inventory
     * @param  int  $thresholdValue
     * @param  int  $currentValue
     * @return static
     */
    public static function lowStock(ProductInventory $inventory, int $thresholdValue, int $currentValue)
    {
        $severity = $currentValue <= ($thresholdValue / 2) ? 'critical' : 'warning';
        return new static($inventory, 'low_stock', $thresholdValue, $currentValue, $severity);
    }

    /**
     * Create a high stock threshold event.
     *
     * @param  \App\Models\ProductInventory  $inventory
     * @param  int  $thresholdValue
     * @param  int  $currentValue
     * @return static
     */
    public static function highStock(ProductInventory $inventory, int $thresholdValue, int $currentValue)
    {
        $severity = $currentValue >= ($thresholdValue * 1.5) ? 'critical' : 'warning';
        return new static($inventory, 'high_stock', $thresholdValue, $currentValue, $severity);
    }

    /**
     * Create an expiry threshold event.
     *
     * @param  \App\Models\ProductInventory  $inventory
     * @param  int  $thresholdValue Days until expiry threshold
     * @param  int  $currentValue Days until expiry
     * @return static
     */
    public static function expiringSoon(ProductInventory $inventory, int $thresholdValue, int $currentValue)
    {
        $severity = $currentValue <= ($thresholdValue / 2) ? 'critical' : 'warning';
        return new static($inventory, 'expiring_soon', $thresholdValue, $currentValue, $severity);
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'inventory.threshold.' . $this->thresholdType;
    }

    /**
     * Get the event payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'inventory_id' => $this->inventory->id,
            'product_id' => $this->inventory->product_id,
            'product_name' => $this->inventory->product->name ?? 'Unknown Product',
            'location_id' => $this->inventory->location_id,
            'location_name' => $this->inventory->location->name ?? 'Unknown Location',
            'threshold_type' => $this->thresholdType,
            'threshold_value' => $this->thresholdValue,
            'current_value' => $this->currentValue,
            'severity' => $this->severity,
        ];
    }

    /**
     * Get the notification message.
     *
     * @return string
     */
    public function getNotificationMessage(): string
    {
        $productName = $this->inventory->product->name ?? 'Unknown Product';
        $locationName = $this->inventory->location->name ?? 'Unknown Location';

        switch ($this->thresholdType) {
            case 'low_stock':
                return "Low stock alert: {$productName} at {$locationName} has {$this->currentValue} units (threshold: {$this->thresholdValue})";
            
            case 'high_stock':
                return "High stock alert: {$productName} at {$locationName} has {$this->currentValue} units (threshold: {$this->thresholdValue})";
            
            case 'expiring_soon':
                return "Expiry alert: {$productName} at {$locationName} expires in {$this->currentValue} days (threshold: {$this->thresholdValue} days)";
            
            default:
                return "Inventory threshold alert: {$productName} at {$locationName}";
        }
    }
}