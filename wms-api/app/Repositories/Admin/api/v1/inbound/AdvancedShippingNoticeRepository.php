<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\AdvancedShippingNotice;
use App\Repositories\BaseRepository;

class AdvancedShippingNoticeRepository extends BaseRepository
{
    public function __construct(AdvancedShippingNotice $asn)
    {
        parent::__construct($asn);
    }
}
