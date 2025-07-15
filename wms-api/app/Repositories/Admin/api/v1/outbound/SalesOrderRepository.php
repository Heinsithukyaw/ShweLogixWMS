<?php

namespace App\Repositories\Admin\api\v1\outbound;

use App\Models\SalesOrder;
use App\Repositories\BaseRepository;

class SalesOrderRepository extends BaseRepository
{
    public function __construct(SalesOrder $salesOrder)
    {
        parent::__construct($salesOrder);
    }
} 