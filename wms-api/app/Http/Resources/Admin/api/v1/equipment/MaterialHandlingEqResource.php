<?php

namespace App\Http\Resources\Admin\api\v1\equipment;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialHandlingEqResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'material-equipment-eqs.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'mhe_code'  => $this->mhe_code,
            'mhe_name' => $this->mhe_name,
            'mhe_type' => $this->mhe_type,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'purchase_date' => $this->purchase_date,
            'warranty_expire_date' => $this->warranty_expire_date,
            'capacity' => $this->capacity,
            'capacity_unit' => $this->capacity_unit,
            'current_location_detail' => $this->current_location_detail,
            'home_location' => $this->home_location,
            'shift_availability' => $this->shift_availability,
            'operator_assigned' => $this->operator_assigned,
            'maintenance_schedule_type' => $this->maintenance_schedule_type,
            'maintenance_frequency' => $this->maintenance_frequency,
            'last_maintenance_date' => $this->last_maintenance_date,
            'last_service_type' => $this->last_service_type,
            'last_maintenance_due_date' => $this->last_maintenance_due_date,
            'safety_inspection_due_date' => $this->safety_inspection_due_date,
            'safety_certification_expire_date' => $this->safety_certification_expire_date,
            'safety_features' => $this->safety_features,
            'uptime_percentage_monthly' => $this->uptime_percentage_monthly,
            'maintenance_cost' => $this->maintenance_cost,
            'currency' => $this->currency,
            'energy_consumption_per_hour' => $this->energy_consumption_per_hour,
            'depreciation_start_date' => $this->depreciation_start_date,
            'depreciation_method' => $this->depreciation_method,
            'estimated_useful_life_year' => $this->estimated_useful_life_year,
            'supplier_id' => $this->supplier?->id,
            'supplier_code' => $this->supplier?->party_code,
            'supplier_name' => $this->supplier?->party_name,
            'supplier_contact_id' => $this->supplierContact?->id,
            'supplier_contact_name' => $this->supplierContact?->contact_name,
            'supplier_contact_email' => $this->supplierContact?->email,
            'expected_replacement_date' => $this->expected_replacement_date,
            'disposal_date' => $this->disposal_date,
            'replacement_mhe_id' => $this->replacement_mhe_id,
            'remark' => $this->remark,
            'custom_attributes' => $this->custom_attributes,
            'usage_status' => $this->usage_status == 1?'Available':($this->usage_status == 2? 'Maintenance':($this->usage_status == 3? 'In Use':'')),
            'status' => $this->status ==1 ? 'Operational' : 'Under Maintenance',
            'status_value' => $this->status,
            'usage_status_value' => $this->usage_status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
