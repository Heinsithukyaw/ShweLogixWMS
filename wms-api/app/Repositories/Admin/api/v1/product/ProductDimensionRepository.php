<?php

namespace App\Repositories\Admin\api\v1\product;

use App\Models\ProductDimension;
use App\Repositories\BaseRepository;

class ProductDimensionRepository extends BaseRepository
{
    public function __construct(ProductDimension $product)
    {
        parent::__construct($product);
    }
}
