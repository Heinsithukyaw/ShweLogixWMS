<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\PutAwayTask;
use App\Repositories\BaseRepository;

class PutAwayTaskRepository extends BaseRepository
{
    public function __construct(PutAwayTask $put_away_task)
    {
        parent::__construct($put_away_task);
    }
}
