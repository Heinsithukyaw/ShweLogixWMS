<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\ReceivingDock;
use App\Repositories\BaseRepository;

class ReceivingDockRepository extends BaseRepository
{
    public function __construct(ReceivingDock $receiving_dock)
    {
        parent::__construct($receiving_dock);
    }
}
