<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PutAwayTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'put_away_task_code',
        'inbound_shipment_detail_id',
        'assigned_to_id',
        'created_date',
        'due_date',
        'start_time',
        'complete_time',
        'source_location_id',
        'destination_location_id',
        'qty',
        'priority',
        'status',
    ];

    public function assigned_emp()
    {
        return $this->belongsTo(Employee::class, 'assigned_to_id');
    }

    public function inbound_shipment_detail()
    {
        return $this->belongsTo(InboundShipmentDetail::class, 'inbound_shipment_detail_id');
    }

    public function source_location()
    {
        return $this->belongsTo(StagingLocation::class, 'source_location_id');
    }

    public function destination_location()
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

}
