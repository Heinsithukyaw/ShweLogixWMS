<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PalletEquipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'pallet_code',
        'pallet_name',
        'pallet_type',
        'material',
        'manufacturer',
        'length',
        'width',
        'height',
        'weight_capacity',
        'empty_weight',
        'condition',
        'current_location',
        'purchase_date',
        'last_inspection_date',
        'next_inspection_date',
        'pooled_pallet',
        'pool_provider',
        'cost_per_unit',
        'expected_lifespan_year',
        'rfid_tag',
        'barcode',
        'currently_assigned',
        'assigned_shipment',
        'status',
        'notes',
    ];

    
}
