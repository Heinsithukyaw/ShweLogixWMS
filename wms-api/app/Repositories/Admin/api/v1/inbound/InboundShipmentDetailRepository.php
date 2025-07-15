<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\InboundShipmentDetail;
use App\Repositories\BaseRepository;

class InboundShipmentDetailRepository extends BaseRepository
{
    public function __construct(InboundShipmentDetail $inbound_shipment_detail)
    {
        parent::__construct($inbound_shipment_detail);
    }
}
