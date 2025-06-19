<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_term_code',
        'payment_term_name',
        'payment_type',
        'payment_due_day',
        'discount_percent',
        'discount_day',
        'created_by',
        'modified_by',
        'description',
        'status'
    ];

}
