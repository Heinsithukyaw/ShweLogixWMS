<?php

namespace App\Events\Inventory;

use App\Events\BaseEvent;
use App\Models\ProductInventory;

class InventoryChangedEvent extends BaseEvent
{
    /**
     * The product inventory instance.
     *
     * @var \App\Models\ProductInventory
     */
    public $inventory;

    /**
     * The change type.
     *
     * @var string
     */
    public $changeType;

    /**
     * The previous quantity.
     *
     * @var int
     */
    public $previousQuantity;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\ProductInventory  $inventory
     * @param  string  $changeType
     * @param  int  $previousQuantity
     * @return void
     */
    public function __construct(ProductInventory $inventory, string $changeType, int $previousQuantity)
    {
        parent::__construct();
        $this->inventory = $inventory;
        $this->changeType = $changeType;
        $this->previousQuantity = $previousQuantity;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'inventory.changed';
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
            'previous_quantity' => $this->previousQuantity,
            'current_quantity' => $this->inventory->quantity,
            'change_type' => $this->changeType,
            'change_amount' => $this->inventory->quantity - $this->previousQuantity,
            'uom_id' => $this->inventory->uom_id,
            'status' => $this->inventory->status,
        ];
    }
}