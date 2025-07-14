<?php

namespace App\Models\OLAP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OlapDimCustomer extends Model
{
    use HasFactory;

    protected $table = 'olap_dim_customer';

    protected $fillable = [
        'customer_id',
        'customer_code',
        'customer_name',
        'customer_type',
        'customer_group',
        'region',
        'country',
        'state',
        'city',
        'industry',
        'since_date',
        'is_active',
    ];

    protected $casts = [
        'since_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function orderProcessing()
    {
        return $this->hasMany(OlapFactOrderProcessing::class, 'customer_id', 'customer_id');
    }
}