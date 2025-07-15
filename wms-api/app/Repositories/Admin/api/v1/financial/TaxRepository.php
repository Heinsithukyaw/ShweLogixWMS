<?php

namespace App\Repositories\Admin\api\v1\financial;

use App\Models\Tax;
use App\Repositories\BaseRepository;

class TaxRepository extends BaseRepository
{
    public function __construct(Tax $tax)
    {
        parent::__construct($tax);
    }
}
