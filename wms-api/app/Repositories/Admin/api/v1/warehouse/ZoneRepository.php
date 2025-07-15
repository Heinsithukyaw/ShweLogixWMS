<?php

namespace App\Repositories\Admin\api\v1\warehouse;

use App\Models\Zone;
use App\Repositories\BaseRepository;

class ZoneRepository extends BaseRepository
{
    public function __construct(Zone $zone)
    {
        parent::__construct($zone);
    }
}
