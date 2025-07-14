<?php

namespace App\Repositories\Admin\api\v1\outbound;

use App\Models\PickWave;
use App\Repositories\BaseRepository;

class PickWaveRepository extends BaseRepository
{
    public function __construct(PickWave $pickWave)
    {
        parent::__construct($pickWave);
    }
} 