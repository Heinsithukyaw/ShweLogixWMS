<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'uom_id',
        'unit_of_measure',
        'warehouse_code',
        'location',
        'reorder_level',
        'batch_no',
        'lot_no',
        'packing_qty',
        'whole_qty',
        'loose_qty',
        'stock_rotation_policy'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit_of_measure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }
}
