<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_code',
        'area_name',
        'area_type',
        'warehouse_id',
        'responsible_person',           
        'phone_number',
        'email',
        'location_description',
        'capacity',
        'dimensions',
        'environmental_conditions',
        'equipment',
        'custom_attributes',
        'status',
    ];

        public function warehouse()
        {
            return $this->belongsTo(Warehouse::class, 'warehouse_id');
        }

}
