<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_inspection_code',
        'inbound_shipment_detail_id',
        'inspector_name',
        'inspection_date',
        'status',
        'rejection_reason',
        'sample_size',
        'notes',
        'corrective_action',
        'image_path',
    ];

    public function inbound_shipment_detail()
    {
        return $this->belongsTo(InboundShipmentDetail::class, 'inbound_shipment_detail_id');
    }
}
