<?php

namespace App\Repositories\Admin\api\v1\geographical;

use App\Models\Country;
use App\Repositories\BaseRepository;

class CountryRepository extends BaseRepository
{
    public function __construct(Country $country)
    {
        parent::__construct($country);
    }
}
