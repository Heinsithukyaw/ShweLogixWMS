<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseLayout extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'name',
        'description',
        'layout_data',
        'is_active',
        'is_simulation',
    ];

    protected $casts = [
        'layout_data' => 'array',
        'is_active' => 'boolean',
        'is_simulation' => 'boolean',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function optimizationMetrics()
    {
        return $this->hasMany(OptimizationMetric::class);
    }
}