<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_code',
        'state_name',
        'state_type',
        'capital',
        'country_id',
        'postal_code_prefix',
        'created_by',
        'last_modified_by',
        'status'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    
}
