<?php

namespace App\Repositories\Admin\api\v1\financial;

use App\Models\Currency;
use App\Repositories\BaseRepository;

class CurrencyRepository extends BaseRepository
{
    public function __construct(Currency $currency)
    {
        parent::__construct($currency);
    }
}
