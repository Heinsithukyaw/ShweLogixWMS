<?php

namespace App\Repositories\Admin\api\v1\business;

use App\Models\BusinessParty;
use App\Repositories\BaseRepository;

class BusinessPartyRepository extends BaseRepository
{
    public function __construct(BusinessParty $party)
    {
        logger("Party Repo - ".$party);
        parent::__construct($party);
    }
}
