<?php

namespace App\Repositories\Admin\api\v1\business;

use App\Models\BusinessContact;
use App\Repositories\BaseRepository;

class BusinessContactRepository extends BaseRepository
{
    public function __construct(BusinessContact $contact)
    {
        parent::__construct($contact);
    }
}
