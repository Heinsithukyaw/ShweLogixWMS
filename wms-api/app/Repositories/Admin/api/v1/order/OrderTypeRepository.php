<?php

namespace App\Repositories\Admin\api\v1\order;

use App\Models\OrderType;
use App\Repositories\BaseRepository;

class OrderTypeRepository extends BaseRepository
{
    public function __construct(OrderType $order_type)
    {
        parent::__construct($order_type);
    }
}
