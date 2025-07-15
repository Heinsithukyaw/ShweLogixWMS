<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickWave extends Model
{
    use HasFactory;

    protected $fillable = [
        'wave_number',
        'wave_date',
        'status',
        'total_orders',
        'total_items',
        'assigned_to',
        'planned_start_time',
        'actual_start_time',
        'planned_completion_time',
        'actual_completion_time',
        'notes',
        'pick_strategy',
        'created_by',
        'last_modified_by',
    ];

    protected $casts = [
        'wave_date' => 'date',
        'planned_start_time' => 'datetime',
        'actual_start_time' => 'datetime',
        'planned_completion_time' => 'datetime',
        'actual_completion_time' => 'datetime',
    ];

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function pickTasks()
    {
        return $this->hasMany(PickTask::class, 'wave_id');
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }
} 