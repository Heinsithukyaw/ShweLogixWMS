<?php

namespace App\Repositories\Admin\api\v1\warehouse;

use App\Models\Warehouse;
use App\Repositories\BaseRepository;

class WarehouseRepository extends BaseRepository
{
    public function __construct(Warehouse $warehouse)
    {
        parent::__construct($warehouse);
    }
}
