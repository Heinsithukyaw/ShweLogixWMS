<?php

namespace App\Repositories\Admin\api\v1\shipping;

use App\Models\ShippingCarrier;
use App\Repositories\BaseRepository;

class ShippingCarrierRepository extends BaseRepository
{
    public function __construct(ShippingCarrier $shipping_carrier)
    {
        parent::__construct($shipping_carrier);
    }
}
