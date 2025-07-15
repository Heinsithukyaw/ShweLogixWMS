<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostType extends Model
{
    use HasFactory;

    protected $fillable = [
        'cost_code',
        'cost_name',
        'cost_type',
        'category_id',
        'subcategory_id',
        'created_by',
        'modified_by',
        'status'
    ];

    public function cost_category()
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    public function cost_subcategory()
    {
        return $this->belongsTo(FinancialCategory::class, 'subcategory_id');
    }

}
