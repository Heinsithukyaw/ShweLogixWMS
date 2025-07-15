<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageEquipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_equipment_code',
        'storage_equipment_name',
        'storage_equipment_type',
        'manufacturer',
        'model',
        'serial_number',
        'purchase_date',
        'warranty_expire_date',
        'zone_id',
        'aisle',
        'bay',
        'level',
        'installation_date',
        'last_inspection_date',
        'next_inspection_due_date',
        'inspection_frequency',
        'max_weight_capacity',
        'max_volume_capacity',
        'length',
        'width',
        'height',
        'material',
        'shelves_tiers_number',
        'adjustability',
        'safety_features',
        'load_type',
        'accessibility',
        'uptime_percentage_monthly',
        'maintenance_cost',
        'currency_unit',
        'depreciation_start_date',
        'depreciation_method',
        'estimated_useful_life_years',
        'supplier_id',
        'expected_replacement_date',
        'disposal_date',
        'replacement_mhe_code',
        'remark',
        'custom_attributes',
        'status',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id')->select(['id','zone_code','zone_name']);
    }

    public function supplier()
    {
        return $this->belongsTo(BusinessParty::class, 'supplier_id')->select(['id','party_code','party_name']);
    }
    
}
