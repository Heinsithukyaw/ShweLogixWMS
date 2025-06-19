<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\CrossDockingTask;
use App\Repositories\BaseRepository;

class CrossDockingTaskRepository extends BaseRepository
{
    public function __construct(CrossDockingTask $cross_docking_task)
    {
        parent::__construct($cross_docking_task);
    }
}
