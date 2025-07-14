<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesOrder;
use App\Models\Employee;
use App\Models\User;

class PackOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pack_order_number',
        'sales_order_id',
        'packing_station_id',
        'assigned_to',
        'pack_status',
        'pack_priority',
        'total_items',
        'packed_items',
        'estimated_time',
        'actual_time',
        'assigned_at',
        'started_at',
        'completed_at',
        'packing_notes',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_items' => 'integer',
        'packed_items' => 'integer',
        'estimated_time' => 'decimal:2',
        'actual_time' => 'decimal:2',
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the sales order that owns the pack order.
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the packing station that owns the pack order.
     */
    public function packingStation()
    {
        return $this->belongsTo(PackingStation::class);
    }

    /**
     * Get the employee assigned to the pack order.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    /**
     * Get the user who created the pack order.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the packed cartons for the pack order.
     */
    public function packedCartons()
    {
        return $this->hasMany(PackedCarton::class);
    }

    /**
     * Scope a query to only include pending pack orders.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('pack_status', 'pending');
    }

    /**
     * Scope a query to only include assigned pack orders.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssigned($query)
    {
        return $query->where('pack_status', 'assigned');
    }

    /**
     * Scope a query to only include in-progress pack orders.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInProgress($query)
    {
        return $query->where('pack_status', 'in_progress');
    }

    /**
     * Scope a query to only include completed pack orders.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('pack_status', 'packed');
    }

    /**
     * Check if the pack order is complete.
     *
     * @return bool
     */
    public function isComplete()
    {
        return $this->packed_items >= $this->total_items;
    }

    /**
     * Calculate the packing progress percentage.
     *
     * @return float
     */
    public function getProgressPercentage()
    {
        if ($this->total_items > 0) {
            return round(($this->packed_items / $this->total_items) * 100, 2);
        }
        
        return 0;
    }
}