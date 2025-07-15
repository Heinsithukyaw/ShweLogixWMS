<?php

namespace App\Repositories\Admin\api\v1\uom;

use App\Models\UnitOfMeasure;
use App\Repositories\BaseRepository;

class UnitOfMeasureRepository extends BaseRepository
{
    public function __construct(UnitOfMeasure $uom)
    {
        parent::__construct($uom);
    }
}
