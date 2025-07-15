<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\ReceivingAppointment;
use App\Repositories\BaseRepository;

class ReceivingAppointmentRepository extends BaseRepository
{
    public function __construct(ReceivingAppointment $receiving_appointment)
    {
        parent::__construct($receiving_appointment);
    }
}
