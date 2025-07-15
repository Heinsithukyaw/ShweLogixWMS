<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'item_code',
        'item_description',
        'quantity_ordered',
        'quantity_allocated',
        'quantity_picked',
        'quantity_packed',
        'quantity_shipped',
        'uom_id',
        'unit_price',
        'total_price',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'line_total',
        'notes',
        'status',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:3',
        'quantity_allocated' => 'decimal:3',
        'quantity_picked' => 'decimal:3',
        'quantity_packed' => 'decimal:3',
        'quantity_shipped' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function uom()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function pickTasks()
    {
        return $this->hasMany(PickTask::class);
    }
} 