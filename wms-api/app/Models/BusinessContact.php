<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_code',
        'contact_name',
        'business_party_id',
        'designation',
        'department',
        'phone_number',
        'email',
        'address',
        'country',
        'preferred_contact_method',
        'status',
        'notes'
    ];

    public function business_party()
    {
        return $this->belongsTo(BusinessParty::class, 'business_party_id');
    }


}
