<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvancedShippingNotice extends Model
{
    use HasFactory;

    protected $fillable = [
        'asn_code',
        'supplier_id',
        'purchase_order_id',
        'expected_arrival',
        'carrier_id',
        'tracking_number',
        'total_items',
        'total_pallet',
        'status',
        'notes',
        'received_date',
    ];

    public function supplier()
    {
        return $this->belongsTo(BusinessParty::class, 'supplier_id');
    }

    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id');
    }

}
