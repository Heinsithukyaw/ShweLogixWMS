<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnloadingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'unloading_session_code',
        'inbound_shipment_id',
        'dock_id',
        'start_time',
        'end_time',
        'status',
        'supervisor_id',
        'total_pallets_unloaded',
        'total_items_unloaded',
        'equipment_used',
        'notes',
    ];

    public function inbound_shipment()
    {
        return $this->belongsTo(InboundShipment::class, 'inbound_shipment_id');
    }

    public function dock()
    {
        return $this->belongsTo(DockEquipment::class, 'dock_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    
}
