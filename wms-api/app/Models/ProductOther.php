<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOther extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'manufacture_date',
        'expire_date',
        'abc_category_value',
        'abc_category_activity',
        'remark',
        'custom_attributes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    
}
