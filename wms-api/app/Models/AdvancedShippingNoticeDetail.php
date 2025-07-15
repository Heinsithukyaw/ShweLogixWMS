<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvancedShippingNoticeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'asn_detail_code',
        'asn_id',
        'item_id',
        'item_description',
        'expected_qty',
        'uom_id',
        'lot_number',
        'expiration_date',
        'received_qty',
        'variance',
        'status',
        'location_id',
        'pallet_id',
        'notes',
    ];

    public function advanced_shipping_notice()
    {
        return $this->belongsTo(AdvancedShippingNotice::class, 'asn_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function unit_of_measure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function zoneLocation()
    {
        return $this->belongsTo(Zone::class, 'location_id');
    }

    public function pallet_equipment()
    {
        return $this->belongsTo(PalletEquipment::class, 'pallet_id');
    }


}
