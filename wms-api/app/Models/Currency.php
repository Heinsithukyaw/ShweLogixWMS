<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;
    protected $fillable = [
        'currency_code',
        'currency_name',
        'symbol',
        'country',
        'exchange_rate',
        'base_currency',
        'decimal_places',
        'created_by',
        'modified_by',
        'status'
    ];

}
