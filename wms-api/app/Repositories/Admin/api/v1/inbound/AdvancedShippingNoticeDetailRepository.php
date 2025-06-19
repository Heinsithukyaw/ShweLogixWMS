<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\AdvancedShippingNoticeDetail;
use App\Repositories\BaseRepository;

class AdvancedShippingNoticeDetailRepository extends BaseRepository
{
    public function __construct(AdvancedShippingNoticeDetail $asn_detail)
    {
        parent::__construct($asn_detail);
    }
}
