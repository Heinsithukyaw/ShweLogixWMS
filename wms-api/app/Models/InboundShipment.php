<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboundShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_code',
        'supplier_id',
        'carrier_id',
        'staging_location_id',
        'purchase_order_id',
        'expected_arrival',
        'actual_arrival',
        'status',
        'version_control',
        'trailer_number',
        'seal_number',
        'total_pallets',
        'total_weight',
        'notes',
    ];

    public function supplier()
    {
        return $this->belongsTo(BusinessParty::class, 'supplier_id');
    }

    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id');
    }

    public function stagingLocation()
    {
        return $this->belongsTo(StagingLocation::class, 'staging_location_id');
    }

}
