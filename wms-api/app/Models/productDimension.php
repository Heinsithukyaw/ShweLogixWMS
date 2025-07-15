<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productDimension extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'dimension_use',
        'length',
        'width',
        'height',
        'weight',
        'volume',
        'storage_volume',
        'space_area',
        'units_per_box',
        'boxes_per_pallet',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
