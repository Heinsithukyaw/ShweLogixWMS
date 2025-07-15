<?php

namespace App\Repositories\Admin\api\v1\geographical;

use App\Models\City;
use App\Repositories\BaseRepository;

class CityRepository extends BaseRepository
{
    public function __construct(City $city)
    {
        parent::__construct($city);
    }
}
