<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\InboundShipment;
use App\Repositories\BaseRepository;

class InboundShipmentRepository extends BaseRepository
{
    public function __construct(InboundShipment $asn)
    {
        parent::__construct($asn);
    }
}
