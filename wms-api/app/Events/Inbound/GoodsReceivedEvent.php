<?php

namespace App\Events\Inbound;

use App\Events\BaseEvent;
use App\Models\GoodReceivedNote;

class GoodsReceivedEvent extends BaseEvent
{
    /**
     * The GRN instance.
     *
     * @var \App\Models\GoodReceivedNote
     */
    public $grn;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\GoodReceivedNote  $grn
     * @return void
     */
    public function __construct(GoodReceivedNote $grn)
    {
        parent::__construct();
        $this->grn = $grn;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'goods.received';
    }

    /**
     * Get the event payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'id' => $this->grn->id,
            'grn_number' => $this->grn->grn_number,
            'asn_id' => $this->grn->asn_id,
            'inbound_shipment_id' => $this->grn->inbound_shipment_id,
            'received_date' => $this->grn->received_date,
            'status' => $this->grn->status,
            'items' => $this->grn->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'received_quantity' => $item->received_quantity,
                    'quality_status' => $item->quality_status,
                ];
            })->toArray(),
        ];
    }
}