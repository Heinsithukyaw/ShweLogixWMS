<?php

namespace App\Repositories\Admin\api\v1\warehouse;

use App\Models\Location;
use App\Repositories\BaseRepository;

class LocationRepository extends BaseRepository
{
    public function __construct(Location $location)
    {
        parent::__construct($location);
    }
}
