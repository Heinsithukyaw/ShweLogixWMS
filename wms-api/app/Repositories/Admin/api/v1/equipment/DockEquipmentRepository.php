<?php

namespace App\Repositories\Admin\api\v1\equipment;

use App\Models\DockEquipment;
use App\Repositories\BaseRepository;

class DockEquipmentRepository extends BaseRepository
{
    public function __construct(DockEquipment $dock_equipment)
    {
        parent::__construct($dock_equipment);
    }
}
