<?php

namespace App\Repositories\Admin\api\v1\equipment;

use App\Models\MaterialHandlingEq;
use App\Repositories\BaseRepository;

class MaterialHandlingEqRepository extends BaseRepository
{
    public function __construct(MaterialHandlingEq $material_handling_eq)
    {
        parent::__construct($material_handling_eq);
    }
}
