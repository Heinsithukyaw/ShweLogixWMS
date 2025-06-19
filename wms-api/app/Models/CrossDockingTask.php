<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrossDockingTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'cross_docking_task_code',
        'asn_id',
        'asn_detail_id',
        'item_id',
        'item_description',
        'qty',
        'source_location_id',
        'destination_location_id',
        'outbound_shipment_id',
        'assigned_to_id',
        'priority',
        'status',
        'created_date',
        'start_time',
        'complete_time',
    ];

    public function asn()
    {
        return $this->belongsTo(AdvancedShippingNotice::class, 'asn_id');
    }

    public function asn_detail()
    {
        return $this->belongsTo(AdvancedShippingNoticeDetail::class, 'asn_detail_id');
    }

    public function assigned_emp()
    {
        return $this->belongsTo(Employee::class, 'assigned_to_id');
    }

    public function source_location()
    {
        return $this->belongsTo(Location::class, 'source_location_id');
    }

    public function destination_location()
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }
    
}
