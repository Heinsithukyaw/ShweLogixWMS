<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Product;
use App\Models\User;

class BackOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'sales_order_item_id',
        'product_id',
        'backordered_quantity',
        'fulfilled_quantity',
        'backorder_status',
        'expected_fulfillment_date',
        'backorder_reason',
        'fulfillment_options',
        'auto_fulfill',
        'created_by',
    ];

    protected $casts = [
        'backordered_quantity' => 'decimal:3',
        'fulfilled_quantity' => 'decimal:3',
        'expected_fulfillment_date' => 'date',
        'fulfillment_options' => 'array',
        'auto_fulfill' => 'boolean',
    ];

    // Relationships
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('backorder_status', 'pending');
    }

    public function scopeAutoFulfill($query)
    {
        return $query->where('auto_fulfill', true);
    }

    public function scopeExpectedToday($query)
    {
        return $query->where('expected_fulfillment_date', now()->toDateString());
    }

    public function scopeOverdue($query)
    {
        return $query->where('expected_fulfillment_date', '<', now()->toDateString())
                    ->whereIn('backorder_status', ['pending', 'partially_fulfilled']);
    }

    // Methods
    public function getRemainingQuantity()
    {
        return $this->backordered_quantity - $this->fulfilled_quantity;
    }

    public function isFullyFulfilled()
    {
        return $this->fulfilled_quantity >= $this->backordered_quantity;
    }

    public function isOverdue()
    {
        return $this->expected_fulfillment_date && 
               $this->expected_fulfillment_date < now()->toDate() &&
               !$this->isFullyFulfilled();
    }

    public function canAutoFulfill()
    {
        return $this->auto_fulfill && 
               $this->backorder_status === 'pending' &&
               $this->hasAvailableInventory();
    }

    public function hasAvailableInventory()
    {
        // Check if product has sufficient inventory
        $availableQuantity = $this->product->productInventories()
            ->where('available_quantity', '>', 0)
            ->sum('available_quantity');
            
        return $availableQuantity >= $this->getRemainingQuantity();
    }

    public function fulfill($quantity)
    {
        $remainingQuantity = $this->getRemainingQuantity();
        $fulfillQuantity = min($quantity, $remainingQuantity);
        
        $this->fulfilled_quantity += $fulfillQuantity;
        
        if ($this->isFullyFulfilled()) {
            $this->backorder_status = 'fulfilled';
        } else {
            $this->backorder_status = 'partially_fulfilled';
        }
        
        $this->save();
        
        return $fulfillQuantity;
    }

    public function updateExpectedDate($date, $reason = null)
    {
        $this->expected_fulfillment_date = $date;
        
        if ($reason) {
            $options = $this->fulfillment_options ?? [];
            $options['date_change_reason'] = $reason;
            $options['date_changed_at'] = now()->toISOString();
            $this->fulfillment_options = $options;
        }
        
        $this->save();
    }
}