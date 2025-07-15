<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_code',
        'location_name',
        'location_type',
        'zone_id',
        'aisle',
        'row',
        'level',
        'bin',
        'capacity',
        'capacity_unit',
        'restrictions',
        'bar_code',
        'description',
        'utilization',
        'status',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }
}
