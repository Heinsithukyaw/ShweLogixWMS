<?php

namespace App\Models\Returns;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Location;

class ReturnReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'return_authorization_id',
        'received_by',
        'warehouse_id',
        'location_id',
        'received_at',
        'overall_condition',
        'inspection_notes',
        'photos',
        'quality_check_required',
        'quality_check_completed',
        'quality_checked_by',
        'quality_checked_at'
    ];

    protected $casts = [
        'photos' => 'array',
        'received_at' => 'datetime',
        'quality_checked_at' => 'datetime',
        'quality_check_required' => 'boolean',
        'quality_check_completed' => 'boolean'
    ];

    public function returnAuthorization(): BelongsTo
    {
        return $this->belongsTo(ReturnAuthorization::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function qualityCheckedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quality_checked_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnReceiptItem::class);
    }

    public function getTotalItemsAttribute()
    {
        return $this->items()->sum('received_quantity');
    }

    public function getTotalRefundAttribute()
    {
        return $this->items()->sum('refund_amount');
    }

    public function isQualityCheckComplete(): bool
    {
        return $this->quality_check_completed;
    }

    public function requiresQualityCheck(): bool
    {
        return $this->quality_check_required && !$this->quality_check_completed;
    }
}