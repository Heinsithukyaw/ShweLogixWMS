<?php

namespace App\Models\Financial;

use App\Models\Warehouse;
use App\Models\Zone;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageCost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'zone_id',
        'location_id',
        'cost_per_unit',
        'unit_type',
        'effective_date',
        'expiry_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'cost_per_unit' => 'decimal:2',
    ];

    /**
     * Get the warehouse that owns this storage cost.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the zone that owns this storage cost.
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the location that owns this storage cost.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}