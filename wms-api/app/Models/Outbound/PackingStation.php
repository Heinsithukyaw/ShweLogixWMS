<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Models\Employee;

class PackingStation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'station_code',
        'station_name',
        'warehouse_id',
        'zone_id',
        'station_type',
        'station_status',
        'capabilities',
        'max_weight_kg',
        'equipment_list',
        'assigned_to',
        'is_automated'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'capabilities' => 'json',
        'equipment_list' => 'json',
        'is_automated' => 'boolean',
        'max_weight_kg' => 'decimal:2'
    ];

    /**
     * Get the warehouse that owns the packing station.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the zone that owns the packing station.
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the employee assigned to the packing station.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    /**
     * Get the pack orders for the packing station.
     */
    public function packOrders()
    {
        return $this->hasMany(PackOrder::class);
    }

    /**
     * Get the packed cartons for the packing station.
     */
    public function packedCartons()
    {
        return $this->hasMany(PackedCarton::class);
    }
}