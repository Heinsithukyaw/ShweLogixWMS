<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\UnloadingSession;
use App\Repositories\BaseRepository;

class UnloadingSessionRepository extends BaseRepository
{
    public function __construct(UnloadingSession $unloading_session)
    {
        parent::__construct($unloading_session);
    }
}
