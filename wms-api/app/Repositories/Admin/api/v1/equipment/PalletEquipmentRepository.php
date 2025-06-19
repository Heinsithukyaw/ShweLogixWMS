<?php

namespace App\Repositories\Admin\api\v1\equipment;

use App\Models\PalletEquipment;
use App\Repositories\BaseRepository;

class PalletEquipmentRepository extends BaseRepository
{
    public function __construct(PalletEquipment $pallet_equipment)
    {
        parent::__construct($pallet_equipment);
    }
}
