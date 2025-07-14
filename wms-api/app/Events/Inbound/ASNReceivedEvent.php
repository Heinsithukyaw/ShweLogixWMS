<?php

namespace App\Events\Inbound;

use App\Events\BaseEvent;
use App\Models\AdvancedShippingNotice;

class ASNReceivedEvent extends BaseEvent
{
    /**
     * The ASN instance.
     *
     * @var \App\Models\AdvancedShippingNotice
     */
    public $asn;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\AdvancedShippingNotice  $asn
     * @return void
     */
    public function __construct(AdvancedShippingNotice $asn)
    {
        parent::__construct();
        $this->asn = $asn;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'asn.received';
    }

    /**
     * Get the event payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'id' => $this->asn->id,
            'asn_number' => $this->asn->asn_number,
            'supplier_id' => $this->asn->supplier_id,
            'expected_arrival_date' => $this->asn->expected_arrival_date,
            'status' => $this->asn->status,
            'details' => $this->asn->details->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'expected_quantity' => $detail->expected_quantity,
                ];
            })->toArray(),
        ];
    }
}