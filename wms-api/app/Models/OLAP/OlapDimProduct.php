<?php

namespace App\Models\OLAP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class OlapDimProduct extends Model
{
    use HasFactory;

    protected $table = 'olap_dim_product';

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'category',
        'subcategory',
        'brand',
        'uom_code',
        'unit_cost',
        'unit_price',
        'product_group',
        'product_type',
        'is_active',
    ];

    protected $casts = [
        'unit_cost' => 'float',
        'unit_price' => 'float',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(OlapFactInventoryMovement::class, 'product_id', 'product_id');
    }
}