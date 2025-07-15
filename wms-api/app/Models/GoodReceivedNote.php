<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodReceivedNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_code',
        'inbound_shipment_id',
        'purchase_order_id',
        'supplier_id',
        'received_date',
        'created_by',
        'approved_by',
        'total_items',
        'notes',
        'status',
    ];

    public function inbound_shipment()
    {
        return $this->belongsTo(InboundShipment::class, 'inbound_shipment_id');
    }

    public function supplier()
    {
        return $this->belongsTo(BusinessParty::class, 'supplier_id');
    }

    public function created_emp()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function approved_emp()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }
    
}
