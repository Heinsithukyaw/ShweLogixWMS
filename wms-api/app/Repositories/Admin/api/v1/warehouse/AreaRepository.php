<?php

namespace App\Repositories\Admin\api\v1\warehouse;

use App\Models\Area;
use App\Repositories\BaseRepository;

class AreaRepository extends BaseRepository
{
    public function __construct(Area $area)
    {
        parent::__construct($area);
    }
}
