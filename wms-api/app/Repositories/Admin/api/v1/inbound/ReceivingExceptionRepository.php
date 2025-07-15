<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\ReceivingException;
use App\Repositories\BaseRepository;

class ReceivingExceptionRepository extends BaseRepository
{
    public function __construct(ReceivingException $receiving_exception)
    {
        parent::__construct($receiving_exception);
    }
}
