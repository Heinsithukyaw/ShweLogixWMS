<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\QualityInspection;
use App\Repositories\BaseRepository;

class QualityInspectionRepository extends BaseRepository
{
    public function __construct(QualityInspection $quality_inspection)
    {
        parent::__construct($quality_inspection);
    }
}
