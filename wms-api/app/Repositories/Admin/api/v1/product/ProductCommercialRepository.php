<?php

namespace App\Repositories\Admin\api\v1\product;

use App\Models\ProductCommercial;
use App\Repositories\BaseRepository;

class ProductCommercialRepository extends BaseRepository
{
    public function __construct(ProductCommercial $product)
    {
        parent::__construct($product);
    }
}
