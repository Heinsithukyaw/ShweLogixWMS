<?php

namespace App\Repositories\Admin\api\v1\product;

use App\Models\ProductInventory;
use App\Repositories\BaseRepository;

class ProductInventoryRepository extends BaseRepository
{
    public function __construct(ProductInventory $product)
    {
        parent::__construct($product);
    }
}
