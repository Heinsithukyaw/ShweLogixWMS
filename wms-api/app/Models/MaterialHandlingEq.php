<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialHandlingEq extends Model
{
    use HasFactory;
    protected $fillable = [
        'mhe_code',
        'mhe_name',
        'mhe_type',
        'manufacturer',
        'model',
        'serial_number',
        'purchase_date',
        'warranty_expire_date',
        'capacity',
        'capacity_unit',
        'current_location_detail',
        'home_location',
        'shift_availability',
        'operator_assigned',
        'maintenance_schedule_type',
        'maintenance_frequency',
        'last_maintenance_date',
        'last_service_type',
        'last_maintenance_due_date',
        'safety_inspection_due_date',
        'safety_certification_expire_date',
        'safety_features',
        'uptime_percentage_monthly',
        'maintenance_cost',
        'currency',
        'energy_consumption_per_hour',
        'depreciation_start_date',
        'depreciation_method',
        'estimated_useful_life_year',
        'supplier_id',
        'supplier_contact_id',
        'expected_replacement_date',
        'disposal_date',
        'replacement_mhe_id',
        'remark',
        'custom_attributes',
        'usage_status',
        'status',
    ];

    public function supplier()
    {
        return $this->belongsTo(BusinessParty::class, 'supplier_id')->select(['id','party_code','party_name']);
    }

    public function supplierContact()
    {
        return $this->belongsTo(BusinessContact::class, 'supplier_contact_id')->select(['id','contact_code','contact_name']);
    }

}
