<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DockEquipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'dock_code',
        'dock_name',
        'dock_type',
        'warehouse_id',
        'area_id',
        'dock_number',
        'capacity',
        'capacity_unit',
        'dimensions',
        'equipment_features',
        'last_maintenance_date',
        'next_maintenance_date',
        'assigned_staff',
        'operating_hours',
        'remarks',
        'custom_attributes',
        'status',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

}
