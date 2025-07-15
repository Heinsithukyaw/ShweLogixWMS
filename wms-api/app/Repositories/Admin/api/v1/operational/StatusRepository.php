<?php

namespace App\Repositories\Admin\api\v1\operational;

use App\Models\Status;
use App\Repositories\BaseRepository;

class StatusRepository extends BaseRepository
{
    public function __construct(Status $status)
    {
        parent::__construct($status);
    }
}
