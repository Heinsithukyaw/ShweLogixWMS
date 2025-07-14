<?php

namespace App\Models\Returns;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\BusinessParty;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;

class ReturnAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'rma_number',
        'customer_id',
        'original_order_id',
        'return_type',
        'status',
        'reason',
        'customer_notes',
        'internal_notes',
        'estimated_value',
        'actual_refund_amount',
        'requested_date',
        'approved_date',
        'expected_return_date',
        'received_date',
        'processed_date',
        'approved_by',
        'processed_by',
        'warehouse_id',
        'return_shipping_info'
    ];

    protected $casts = [
        'return_shipping_info' => 'array',
        'requested_date' => 'date',
        'approved_date' => 'date',
        'expected_return_date' => 'date',
        'received_date' => 'date',
        'processed_date' => 'date',
        'estimated_value' => 'decimal:2',
        'actual_refund_amount' => 'decimal:2'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(BusinessParty::class, 'customer_id');
    }

    public function originalOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'original_order_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnAuthorizationItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ReturnReceipt::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function getTotalItemsAttribute()
    {
        return $this->items()->sum('requested_quantity');
    }

    public function getTotalValueAttribute()
    {
        return $this->items()->sum('total_value');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canBeProcessed(): bool
    {
        return in_array($this->status, ['approved', 'received']);
    }
}