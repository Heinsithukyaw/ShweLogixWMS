<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingAppointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_code',
        'inbound_shipment_id',
        'supplier_id',
        'dock_id',
        'purchase_order_id',
        'scheduled_date',
        'start_time',
        'end_time',
        'status',
        'carrier_name',
        'driver_name',
        'driver_phone_number',
        'trailer_number',
        'estimated_pallet',
        'check_in_time',
        'check_out_time',
        'version_control',
    ];

    public function inbound_shipment()
    {
        return $this->belongsTo(InboundShipment::class, 'inbound_shipment_id');
    }

    public function supplier()
    {
        return $this->belongsTo(BusinessParty::class, 'supplier_id');
    }

    public function dock()
    {
        return $this->belongsTo(DockEquipment::class, 'dock_id');
    }

    
}
