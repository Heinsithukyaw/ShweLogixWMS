<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IoTData extends Model
{
    use HasFactory;

    protected $fillable = [
        'iot_device_id',
        'data_type',
        'data_value',
        'recorded_at',
    ];

    protected $casts = [
        'data_value' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function iotDevice()
    {
        return $this->belongsTo(IoTDevice::class);
    }
}