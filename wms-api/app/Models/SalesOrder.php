<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'order_date',
        'ship_date',
        'status',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'shipping_amount',
        'grand_total',
        'notes',
        'priority',
        'shipment_method',
        'payment_terms',
        'created_by',
        'last_modified_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'ship_date' => 'date',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(BusinessParty::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function pickWaves()
    {
        return $this->hasMany(PickWave::class);
    }

    public function shipments()
    {
        return $this->hasMany(OutboundShipment::class);
    }
} 