<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptimizationMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'warehouse_layout_id',
        'metric_type',
        'value',
        'details',
        'measured_at',
    ];

    protected $casts = [
        'details' => 'array',
        'value' => 'float',
        'measured_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseLayout()
    {
        return $this->belongsTo(WarehouseLayout::class);
    }
}