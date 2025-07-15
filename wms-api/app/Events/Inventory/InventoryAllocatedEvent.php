<?php

namespace App\Events\Inventory;

use App\Events\BaseEvent;
use App\Models\ProductInventory;

class InventoryAllocatedEvent extends BaseEvent
{
    /**
     * The product inventory instance.
     *
     * @var \App\Models\ProductInventory
     */
    public $inventory;

    /**
     * The order ID.
     *
     * @var int
     */
    public $orderId;

    /**
     * The allocated quantity.
     *
     * @var int
     */
    public $allocatedQuantity;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\ProductInventory  $inventory
     * @param  int  $orderId
     * @param  int  $allocatedQuantity
     * @return void
     */
    public function __construct(ProductInventory $inventory, int $orderId, int $allocatedQuantity)
    {
        parent::__construct();
        $this->inventory = $inventory;
        $this->orderId = $orderId;
        $this->allocatedQuantity = $allocatedQuantity;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'inventory.allocated';
    }

    /**
     * Get the event payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'product_id' => $this->inventory->product_id,
            'location_id' => $this->inventory->location_id,
            'order_id' => $this->orderId,
            'allocated_quantity' => $this->allocatedQuantity,
            'available_quantity' => $this->inventory->quantity - $this->allocatedQuantity,
            'uom_id' => $this->inventory->uom_id,
        ];
    }
}