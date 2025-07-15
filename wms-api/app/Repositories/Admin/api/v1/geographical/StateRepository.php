<?php

namespace App\Repositories\Admin\api\v1\geographical;

use App\Models\State;
use App\Repositories\BaseRepository;

class StateRepository extends BaseRepository
{
    public function __construct(State $state)
    {
        parent::__construct($state);
    }
}
