<?php

namespace App\Models\ECommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\SalesOrderItem;

class ReturnOrderItem extends Model
{
    use HasFactory;

    protected $table = 'return_order_items';

    protected $fillable = [
        'return_order_id',
        'original_order_item_id',
        'product_id',
        'quantity_returned',
        'unit_price',
        'return_reason',
        'condition_received',
        'disposition',
        'inspection_notes',
        'restockable'
    ];

    protected $casts = [
        'quantity_returned' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'restockable' => 'boolean'
    ];

    // Relationships
    public function returnOrder()
    {
        return $this->belongsTo(ReturnOrder::class);
    }

    public function originalOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class, 'original_order_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Methods
    public function getTotalRefundAmount()
    {
        return $this->quantity_returned * $this->unit_price;
    }

    public function canRestock()
    {
        return $this->restockable && 
               in_array($this->condition_received, ['new', 'like_new', 'good']) &&
               in_array($this->disposition, ['restock', 'resell']);
    }

    public function setDisposition($disposition, $notes = null)
    {
        $this->disposition = $disposition;
        if ($notes) {
            $this->inspection_notes = $notes;
        }
        
        // Determine if item is restockable based on disposition
        $this->restockable = in_array($disposition, ['restock', 'resell']);
        
        $this->save();
    }
}