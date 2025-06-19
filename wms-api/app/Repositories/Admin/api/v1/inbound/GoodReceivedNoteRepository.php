<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\GoodReceivedNote;
use App\Repositories\BaseRepository;

class GoodReceivedNoteRepository extends BaseRepository
{
    public function __construct(GoodReceivedNote $grn)
    {
        parent::__construct($grn);
    }
}
