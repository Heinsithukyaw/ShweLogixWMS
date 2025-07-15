<?php

namespace App\Repositories\Admin\api\v1\operational;

use App\Models\ActivityType;
use App\Repositories\BaseRepository;

class ActivityTypeRepository extends BaseRepository
{
    public function __construct(ActivityType $activity_type)
    {
        parent::__construct($activity_type);
    }
}
