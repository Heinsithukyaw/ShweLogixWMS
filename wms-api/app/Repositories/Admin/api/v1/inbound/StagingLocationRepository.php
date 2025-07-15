<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\StagingLocation;
use App\Repositories\BaseRepository;

class StagingLocationRepository extends BaseRepository
{
    public function __construct(StagingLocation $staging_location)
    {
        parent::__construct($staging_location);
    }
}
