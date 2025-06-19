<?php

namespace App\Repositories\Admin\api\v1\financial;

use App\Models\PaymentTerm;
use App\Repositories\BaseRepository;

class PaymentTermRepository extends BaseRepository
{
    public function __construct(PaymentTerm $payment_term)
    {
        parent::__construct($payment_term);
    }
}
