<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingCarrier extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrier_code',
        'carrier_name',
        'contact_person',
        'phone_number',
        'email',
        'address',
        'country',
        'contract_details',
        'payment_terms',
        'service_type',
        'tracking_url',
        'performance_rating',
        'capabilities',
        'created_by',
        'last_modified_by',
        'status'
    ];
}



