<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderType extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_type_code',
        'order_type_name',
        'direction',
        'priority_level',
        'default_workflow',
        'description',
        'status'
    ];
}
