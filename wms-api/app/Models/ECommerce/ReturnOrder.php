<?php

namespace App\Models\ECommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SalesOrder;
use App\Models\BusinessParty;

class ReturnOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'return_orders';

    protected $fillable = [
        'return_number',
        'original_order_id',
        'customer_id',
        'return_reason',
        'return_type',
        'return_status',
        'requested_date',
        'approved_date',
        'received_date',
        'processed_date',
        'refund_amount',
        'restocking_fee',
        'return_shipping_cost',
        'inspection_notes',
        'processing_notes',
        'approved_by',
        'processed_by'
    ];

    protected $casts = [
        'requested_date' => 'datetime',
        'approved_date' => 'datetime',
        'received_date' => 'datetime',
        'processed_date' => 'datetime',
        'refund_amount' => 'decimal:2',
        'restocking_fee' => 'decimal:2',
        'return_shipping_cost' => 'decimal:2'
    ];

    // Relationships
    public function originalOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'original_order_id');
    }

    public function customer()
    {
        return $this->belongsTo(BusinessParty::class, 'customer_id');
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnOrderItem::class);
    }

    public function returnHistory()
    {
        return $this->hasMany(ReturnOrderHistory::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function processedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('return_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('return_status', 'approved');
    }

    public function scopeReceived($query)
    {
        return $query->where('return_status', 'received');
    }

    public function scopeProcessed($query)
    {
        return $query->where('return_status', 'processed');
    }

    // Methods
    public function approve($approvedBy, $notes = null)
    {
        $this->return_status = 'approved';
        $this->approved_date = now();
        $this->approved_by = $approvedBy;
        $this->save();

        $this->addHistory('approved', $notes, $approvedBy);
    }

    public function receive($notes = null)
    {
        $this->return_status = 'received';
        $this->received_date = now();
        $this->save();

        $this->addHistory('received', $notes);
    }

    public function process($processedBy, $refundAmount, $notes = null)
    {
        $this->return_status = 'processed';
        $this->processed_date = now();
        $this->processed_by = $processedBy;
        $this->refund_amount = $refundAmount;
        $this->save();

        $this->addHistory('processed', $notes, $processedBy);
    }

    public function calculateRefundAmount()
    {
        $totalRefund = $this->returnItems->sum(function ($item) {
            return $item->quantity_returned * $item->unit_price;
        });

        return $totalRefund - $this->restocking_fee - $this->return_shipping_cost;
    }

    private function addHistory($status, $notes = null, $changedBy = null)
    {
        $this->returnHistory()->create([
            'status' => $status,
            'notes' => $notes,
            'changed_by' => $changedBy ?? auth()->id(),
            'changed_at' => now()
        ]);
    }
}