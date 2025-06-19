<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\ReceivingLaborTracking;
use App\Repositories\BaseRepository;

class ReceivingLaborTrackingRepository extends BaseRepository
{
    public function __construct(ReceivingLaborTracking $receiving_labor_tracking)
    {
        parent::__construct($receiving_labor_tracking);
    }
}
