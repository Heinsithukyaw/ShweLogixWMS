<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_code',
        'tax_description',
        'tax_type',
        'tax_rate',
        'effective_date',
        'tax_calculation_method',
        'tax_authority',
        'notes',
        'status',
    ];


}
