<?php

namespace App\Models\OLAP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;

class OlapDimWarehouse extends Model
{
    use HasFactory;

    protected $table = 'olap_dim_warehouse';

    protected $fillable = [
        'warehouse_id',
        'warehouse_code',
        'warehouse_name',
        'warehouse_type',
        'region',
        'country',
        'state',
        'city',
        'total_area',
        'zone_count',
        'location_count',
        'is_active',
    ];

    protected $casts = [
        'total_area' => 'float',
        'zone_count' => 'integer',
        'location_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(OlapFactInventoryMovement::class, 'warehouse_id', 'warehouse_id');
    }

    public function orderProcessing()
    {
        return $this->hasMany(OlapFactOrderProcessing::class, 'warehouse_id', 'warehouse_id');
    }

    public function warehouseOperations()
    {
        return $this->hasMany(OlapFactWarehouseOperation::class, 'warehouse_id', 'warehouse_id');
    }
}