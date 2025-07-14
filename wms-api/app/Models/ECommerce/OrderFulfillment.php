<?php

namespace App\Models\ECommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SalesOrder;
use App\Models\Product;
use App\Models\ShippingCarrier;

class OrderFulfillment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_fulfillments';

    protected $fillable = [
        'sales_order_id',
        'fulfillment_status',
        'fulfillment_type',
        'priority_level',
        'estimated_ship_date',
        'actual_ship_date',
        'tracking_number',
        'shipping_carrier_id',
        'shipping_cost',
        'automation_rules',
        'fulfillment_notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'estimated_ship_date' => 'datetime',
        'actual_ship_date' => 'datetime',
        'automation_rules' => 'json',
        'shipping_cost' => 'decimal:2'
    ];

    // Relationships
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function shippingCarrier()
    {
        return $this->belongsTo(ShippingCarrier::class);
    }

    public function fulfillmentItems()
    {
        return $this->hasMany(OrderFulfillmentItem::class);
    }

    public function fulfillmentHistory()
    {
        return $this->hasMany(OrderFulfillmentHistory::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('fulfillment_status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('fulfillment_status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('fulfillment_status', 'completed');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority_level', 'high');
    }

    // Methods
    public function canAutomate()
    {
        return !empty($this->automation_rules) && $this->fulfillment_status === 'pending';
    }

    public function calculateShippingCost()
    {
        // Logic to calculate shipping cost based on carrier and items
        $totalWeight = $this->fulfillmentItems->sum('weight');
        $totalVolume = $this->fulfillmentItems->sum('volume');
        
        // This would integrate with carrier APIs
        return $this->shippingCarrier->calculateCost($totalWeight, $totalVolume);
    }

    public function updateStatus($status, $notes = null)
    {
        $this->fulfillment_status = $status;
        if ($notes) {
            $this->fulfillment_notes = $notes;
        }
        $this->save();

        // Create history record
        $this->fulfillmentHistory()->create([
            'status' => $status,
            'notes' => $notes,
            'changed_by' => auth()->id(),
            'changed_at' => now()
        ]);
    }
}