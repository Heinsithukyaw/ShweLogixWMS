<?php

namespace App\Repositories\Admin\api\v1\equipment;

use App\Models\StorageEquipment;
use App\Repositories\BaseRepository;

class StorageEquipmentRepository extends BaseRepository
{
    public function __construct(StorageEquipment $storage_equipment)
    {
        parent::__construct($storage_equipment);
    }
}
