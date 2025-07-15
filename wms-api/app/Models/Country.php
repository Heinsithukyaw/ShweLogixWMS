<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_code',
        'country_name',
        'country_code_3',
        'numeric_code',
        'currency_id',
        'phone_code',
        'capital',
        'created_by',
        'modified_by',
        'status'
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

}
