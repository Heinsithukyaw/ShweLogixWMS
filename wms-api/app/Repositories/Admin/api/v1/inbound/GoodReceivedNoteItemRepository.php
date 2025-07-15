<?php

namespace App\Repositories\Admin\api\v1\inbound;

use App\Models\GoodReceivedNoteItem;
use App\Repositories\BaseRepository;

class GoodReceivedNoteItemRepository extends BaseRepository
{
    public function __construct(GoodReceivedNoteItem $grnItem)
    {
        parent::__construct($grnItem);
    }
}
