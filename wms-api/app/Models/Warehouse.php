<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_code',
        'warehouse_name',
        'warehouse_type',
        'description',
        'address',
        'city',
        'state_region',
        'country',
        'postal_code',
        'phone_number',
        'email',
        'contact_person',
        'manager_name',
        'storage_capacity',
        'operating_hours',
        'custom_attributes',
        'status',
    ];
    
}
