<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_number',
        'wave_id',
        'sales_order_id',
        'sales_order_item_id',
        'product_id',
        'location_id',
        'quantity_requested',
        'quantity_picked',
        'quantity_short',
        'assigned_to',
        'status',
        'priority',
        'assigned_time',
        'start_time',
        'completion_time',
        'notes',
        'pick_method',
        'created_by',
        'last_modified_by',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
        'quantity_picked' => 'decimal:3',
        'quantity_short' => 'decimal:3',
        'assigned_time' => 'datetime',
        'start_time' => 'datetime',
        'completion_time' => 'datetime',
    ];

    public function wave()
    {
        return $this->belongsTo(PickWave::class, 'wave_id');
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

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }
} 