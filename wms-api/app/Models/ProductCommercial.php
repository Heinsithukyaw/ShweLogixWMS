<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCommercial extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'customer_code',
        'bar_code',
        'cost_price',
        'standard_price',
        'currency',
        'discount',
        'supplier_id',
        'manufacturer',
        'country_code',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(BusinessParty::class, 'supplier_id');
    }
}
