<?php

namespace App\Models\Returns;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;

class ReturnReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_receipt_id',
        'return_authorization_item_id',
        'product_id',
        'received_quantity',
        'condition',
        'disposition',
        'inspection_notes',
        'restocking_fee',
        'refund_amount',
        'serial_number',
        'batch_number'
    ];

    protected $casts = [
        'restocking_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2'
    ];

    public function returnReceipt(): BelongsTo
    {
        return $this->belongsTo(ReturnReceipt::class);
    }

    public function returnAuthorizationItem(): BelongsTo
    {
        return $this->belongsTo(ReturnAuthorizationItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getNetRefundAttribute()
    {
        return $this->refund_amount - $this->restocking_fee;
    }

    public function canBeRestocked(): bool
    {
        return $this->disposition === 'restock' && in_array($this->condition, ['new', 'used']);
    }

    public function requiresRefurbishment(): bool
    {
        return $this->disposition === 'refurbish';
    }

    public function shouldBeDisposed(): bool
    {
        return in_array($this->disposition, ['scrap', 'return_to_vendor', 'donate']);
    }
}