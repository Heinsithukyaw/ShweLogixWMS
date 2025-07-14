<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PickTask;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Product;
use App\Models\Location;
use App\Models\Employee;

class PickListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pick_list_id',
        'pick_task_id',
        'sales_order_id',
        'sales_order_item_id',
        'product_id',
        'location_id',
        'lot_number',
        'serial_number',
        'quantity_to_pick',
        'quantity_picked',
        'pick_sequence',
        'pick_status',
        'picked_at',
        'picked_by',
        'pick_notes',
    ];

    protected $casts = [
        'quantity_to_pick' => 'decimal:3',
        'quantity_picked' => 'decimal:3',
        'picked_at' => 'datetime',
    ];

    // Relationships
    public function pickList()
    {
        return $this->belongsTo(PickList::class);
    }

    public function pickTask()
    {
        return $this->belongsTo(PickTask::class);
    }

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

    public function pickedBy()
    {
        return $this->belongsTo(Employee::class, 'picked_by');
    }

    public function pickConfirmations()
    {
        return $this->hasMany(PickConfirmation::class);
    }

    public function pickExceptions()
    {
        return $this->hasMany(PickException::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('pick_status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('pick_status', 'pending');
    }

    public function scopePicked($query)
    {
        return $query->whereIn('pick_status', ['picked', 'short_picked']);
    }

    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Methods
    public function isFullyPicked()
    {
        return $this->quantity_picked >= $this->quantity_to_pick;
    }

    public function isShortPicked()
    {
        return $this->quantity_picked > 0 && $this->quantity_picked < $this->quantity_to_pick;
    }

    public function getRemainingQuantity()
    {
        return $this->quantity_to_pick - $this->quantity_picked;
    }

    public function getPickAccuracy()
    {
        if ($this->quantity_to_pick == 0) {
            return 100;
        }
        
        return round(($this->quantity_picked / $this->quantity_to_pick) * 100, 2);
    }

    public function pick($quantity, $employeeId, $confirmationMethod = 'manual', $notes = null)
    {
        $pickQuantity = min($quantity, $this->getRemainingQuantity());
        
        $this->quantity_picked += $pickQuantity;
        $this->picked_by = $employeeId;
        $this->picked_at = now();
        
        if ($notes) {
            $this->pick_notes = $notes;
        }
        
        // Update status
        if ($this->isFullyPicked()) {
            $this->pick_status = 'picked';
        } else {
            $this->pick_status = 'short_picked';
        }
        
        $this->save();
        
        // Create pick confirmation
        $this->pickConfirmations()->create([
            'pick_task_id' => $this->pick_task_id,
            'employee_id' => $employeeId,
            'confirmed_quantity' => $pickQuantity,
            'confirmation_method' => $confirmationMethod,
            'confirmed_at' => now(),
        ]);
        
        // Update pick list progress
        $this->pickList->updateProgress();
        
        return $pickQuantity;
    }

    public function createException($type, $description, $employeeId)
    {
        return $this->pickExceptions()->create([
            'pick_task_id' => $this->pick_task_id,
            'exception_type' => $type,
            'exception_description' => $description,
            'expected_quantity' => $this->quantity_to_pick,
            'actual_quantity' => $this->quantity_picked,
            'exception_status' => 'open',
            'reported_by' => $employeeId,
            'reported_at' => now(),
        ]);
    }

    public function hasActiveExceptions()
    {
        return $this->pickExceptions()
            ->whereIn('exception_status', ['open', 'investigating'])
            ->exists();
    }

    public function canBePicked()
    {
        return $this->pick_status === 'pending' && !$this->hasActiveExceptions();
    }
}