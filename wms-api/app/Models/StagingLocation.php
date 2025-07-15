<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StagingLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'staging_location_code',
        'staging_location_name',
        'type',
        'warehouse_id',
        'area_id',
        'zone_id',
        'capacity',
        'description',
        'current_usage',
        'last_updated',
        'status'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }


}
