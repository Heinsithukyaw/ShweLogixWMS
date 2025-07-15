<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_code',
        'zone_name',
        'zone_type',
        'area_id',
        'priority',
        'description',
        'status',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'zone_id')->select('id', 'zone_id', 'utilization');
    }


}
