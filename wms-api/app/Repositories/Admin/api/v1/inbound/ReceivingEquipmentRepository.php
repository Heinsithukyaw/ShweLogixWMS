<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\ReceivingEquipment;
use App\Repositories\BaseRepository;

class ReceivingEquipmentRepository extends BaseRepository
{
    public function __construct(ReceivingEquipment $receiving_equipment)
    {
        parent::__construct($receiving_equipment);
    }
}
