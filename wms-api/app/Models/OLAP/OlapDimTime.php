<?php

namespace App\Models\OLAP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OlapDimTime extends Model
{
    use HasFactory;

    protected $table = 'olap_dim_time';

    protected $fillable = [
        'date',
        'day',
        'month',
        'quarter',
        'year',
        'day_name',
        'month_name',
        'is_weekend',
        'is_holiday',
        'fiscal_period',
    ];

    protected $casts = [
        'date' => 'date',
        'day' => 'integer',
        'month' => 'integer',
        'quarter' => 'integer',
        'year' => 'integer',
        'is_weekend' => 'boolean',
        'is_holiday' => 'boolean',
    ];

    public function inventoryMovements()
    {
        return $this->hasMany(OlapFactInventoryMovement::class, 'movement_date', 'date');
    }

    public function orderProcessing()
    {
        return $this->hasMany(OlapFactOrderProcessing::class, 'order_date', 'date');
    }

    public function warehouseOperations()
    {
        return $this->hasMany(OlapFactWarehouseOperation::class, 'operation_date', 'date');
    }
}