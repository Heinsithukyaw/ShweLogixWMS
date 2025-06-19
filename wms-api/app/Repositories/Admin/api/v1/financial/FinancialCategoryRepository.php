<?php

namespace App\Repositories\Admin\api\v1\financial;

use App\Models\FinancialCategory;
use App\Repositories\BaseRepository;

class FinancialCategoryRepository extends BaseRepository
{
    public function __construct(FinancialCategory $financial_category)
    {
        parent::__construct($financial_category);
    }
}
