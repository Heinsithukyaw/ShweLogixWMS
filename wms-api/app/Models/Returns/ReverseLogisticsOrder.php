<?php

namespace App\Models\Returns;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\BusinessParty;
use App\Models\User;
use App\Models\Warehouse;

class ReverseLogisticsOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'type',
        'status',
        'warehouse_id',
        'vendor_id',
        'created_by',
        'approved_by',
        'description',
        'special_instructions',
        'estimated_cost',
        'actual_cost',
        'scheduled_date',
        'completed_date',
        'shipping_info'
    ];

    protected $casts = [
        'shipping_info' => 'array',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'scheduled_date' => 'date',
        'completed_date' => 'date'
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(BusinessParty::class, 'vendor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReverseLogisticsOrderItem::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function getTotalItemsAttribute()
    {
        return $this->items()->sum('quantity');
    }

    public function getTotalCostAttribute()
    {
        return $this->items()->sum('total_cost');
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
        return in_array($this->status, ['approved', 'in_progress']);
    }
}