<?php

namespace App\Repositories\Admin\api\v1\product;

use App\Models\ProductOther;
use App\Repositories\BaseRepository;

class ProductOtherRepository extends BaseRepository
{
    public function __construct(ProductOther $product)
    {
        parent::__construct($product);
    }
}
