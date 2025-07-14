<?php

namespace App\Models\ECommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\SalesOrderItem;

class OrderFulfillmentItem extends Model
{
    use HasFactory;

    protected $table = 'order_fulfillment_items';

    protected $fillable = [
        'order_fulfillment_id',
        'sales_order_item_id',
        'product_id',
        'quantity_ordered',
        'quantity_fulfilled',
        'quantity_remaining',
        'weight',
        'volume',
        'pick_location',
        'fulfillment_status',
        'notes'
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_fulfilled' => 'decimal:2',
        'quantity_remaining' => 'decimal:2',
        'weight' => 'decimal:3',
        'volume' => 'decimal:3'
    ];

    // Relationships
    public function orderFulfillment()
    {
        return $this->belongsTo(OrderFulfillment::class);
    }

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Methods
    public function isFullyFulfilled()
    {
        return $this->quantity_remaining <= 0;
    }

    public function updateFulfillment($quantityFulfilled)
    {
        $this->quantity_fulfilled += $quantityFulfilled;
        $this->quantity_remaining = $this->quantity_ordered - $this->quantity_fulfilled;
        
        if ($this->quantity_remaining <= 0) {
            $this->fulfillment_status = 'completed';
        } else {
            $this->fulfillment_status = 'partial';
        }
        
        $this->save();
    }
}