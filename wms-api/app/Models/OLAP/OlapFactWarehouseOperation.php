<?php

namespace App\Models\OLAP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;
use App\Models\User;

class OlapFactWarehouseOperation extends Model
{
    use HasFactory;

    protected $table = 'olap_fact_warehouse_operations';

    protected $fillable = [
        'warehouse_id',
        'operation_type',
        'user_id',
        'equipment_id',
        'task_count',
        'total_quantity',
        'operation_time_minutes',
        'operation_date',
        'operation_hour',
    ];

    protected $casts = [
        'task_count' => 'integer',
        'total_quantity' => 'float',
        'operation_time_minutes' => 'float',
        'operation_date' => 'date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dimensionWarehouse()
    {
        return $this->belongsTo(OlapDimWarehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function dimensionTime()
    {
        return $this->belongsTo(OlapDimTime::class, 'operation_date', 'date');
    }
}