<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessParty extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_code',
        'party_name',
        'party_type',
        'contact_person',
        'phone_number',
        'email',
        'address',
        'country',
        'tax_vat',
        'business_registration_no',
        'payment_terms',
        'credit_limit',
        'status',
       'custom_attributes',
    ];


}
