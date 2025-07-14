<?php

namespace App\Models\Returns;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;
use App\Models\SalesOrderItem;

class ReturnAuthorizationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_authorization_id',
        'product_id',
        'original_order_item_id',
        'requested_quantity',
        'approved_quantity',
        'received_quantity',
        'unit_price',
        'total_value',
        'condition_expected',
        'condition_actual',
        'item_notes',
        'serial_number',
        'batch_number',
        'expiry_date'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
        'expiry_date' => 'date'
    ];

    public function returnAuthorization(): BelongsTo
    {
        return $this->belongsTo(ReturnAuthorization::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function originalOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'original_order_item_id');
    }

    public function getApprovalRateAttribute()
    {
        return $this->requested_quantity > 0 
            ? ($this->approved_quantity / $this->requested_quantity) * 100 
            : 0;
    }

    public function getReceiptRateAttribute()
    {
        return $this->approved_quantity > 0 
            ? ($this->received_quantity / $this->approved_quantity) * 100 
            : 0;
    }

    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->approved_quantity;
    }

    public function isPendingReceipt(): bool
    {
        return $this->received_quantity < $this->approved_quantity;
    }
}