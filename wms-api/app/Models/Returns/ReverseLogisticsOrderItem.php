<?php

namespace App\Models\Returns;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;

class ReverseLogisticsOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'reverse_logistics_order_id',
        'return_receipt_item_id',
        'product_id',
        'quantity',
        'condition',
        'unit_cost',
        'total_cost',
        'notes',
        'serial_number',
        'batch_number'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2'
    ];

    public function reverseLogisticsOrder(): BelongsTo
    {
        return $this->belongsTo(ReverseLogisticsOrder::class);
    }

    public function returnReceiptItem(): BelongsTo
    {
        return $this->belongsTo(ReturnReceiptItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getCostPerUnitAttribute()
    {
        return $this->quantity > 0 ? $this->total_cost / $this->quantity : 0;
    }
}