<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboundShipmentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbound_detail_code',
        'inbound_shipment_id',
        'product_id',
        'purchase_order_number',
        'expected_qty',
        'received_qty',
        'damaged_qty',
        'lot_number',
        'expiration_date',
        'location_id',
        'received_by',
        'received_date',
        'status'
    ];

    public function inbound_shipment()
    {
        return $this->belongsTo(InboundShipment::class, 'inbound_shipment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

}
