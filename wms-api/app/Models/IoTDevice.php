<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IoTDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'device_id',
        'name',
        'device_type',
        'warehouse_id',
        'location_id',
        'configuration',
        'is_active',
        'last_communication',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
        'last_communication' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function iotData()
    {
        return $this->hasMany(IoTData::class);
    }
}