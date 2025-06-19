<?php

namespace App\Repositories\Admin\api\v1\brand;

use App\Models\Brand;
use App\Repositories\BaseRepository;

class BrandRepository extends BaseRepository
{
    public function __construct(Brand $brand)
    {
        parent::__construct($brand);
    }
}
