<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Product;
use App\Models\Location;
use App\Models\User;

class OrderAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'sales_order_item_id',
        'product_id',
        'location_id',
        'lot_number',
        'serial_number',
        'allocated_quantity',
        'picked_quantity',
        'allocation_status',
        'allocation_type',
        'allocated_at',
        'expires_at',
        'allocation_rules',
        'allocated_by',
    ];

    protected $casts = [
        'allocated_quantity' => 'decimal:3',
        'picked_quantity' => 'decimal:3',
        'allocated_at' => 'datetime',
        'expires_at' => 'datetime',
        'allocation_rules' => 'array',
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

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function allocatedBy()
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('allocation_status', ['allocated', 'partially_picked']);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    // Methods
    public function isFullyPicked()
    {
        return $this->picked_quantity >= $this->allocated_quantity;
    }

    public function getRemainingQuantity()
    {
        return $this->allocated_quantity - $this->picked_quantity;
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function canBePicked()
    {
        return $this->allocation_status === 'allocated' && !$this->isExpired();
    }

    public function updatePickedQuantity($quantity)
    {
        $this->picked_quantity += $quantity;
        
        if ($this->picked_quantity >= $this->allocated_quantity) {
            $this->allocation_status = 'picked';
        } else {
            $this->allocation_status = 'partially_picked';
        }
        
        $this->save();
    }
}