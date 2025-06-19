<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingLaborTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'labor_entry_code', 
        'emp_id',
        'inbound_shipment_id',
        'task_type',
        'start_time',
        'end_time',
        'duration_min',
        'items_processed',
        'pallets_processed',
        'items_min',
        'notes',
        'version_control',
        'status'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function inbound_shipment()
    {
        return $this->belongsTo(InboundShipment::class, 'inbound_shipment_id');
    }
    
}
