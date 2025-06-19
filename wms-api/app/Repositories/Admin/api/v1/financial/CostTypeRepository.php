<?php

namespace App\Repositories\Admin\api\v1\financial;

use App\Models\CostType;
use App\Repositories\BaseRepository;

class CostTypeRepository extends BaseRepository
{
    public function __construct(CostType $costType)
    {
        parent::__construct($costType);
    }
}
