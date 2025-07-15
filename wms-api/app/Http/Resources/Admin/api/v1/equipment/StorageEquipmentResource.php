<?php

namespace App\Http\Resources\Admin\api\v1\equipment;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class StorageEquipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'storage-equipments.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'storage_equipment_code' => $this->storage_equipment_code,
            'storage_equipment_name' => $this->storage_equipment_name,
            'storage_equipment_type' => $this->storage_equipment_type,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'purchase_date' => $this->purchase_date,
            'warranty_expire_date' => $this->warranty_expire_date,
            // 'zone_id' => $this->zone?->id,
            // 'zone_code' => $this->zone?->zone_code,
            // 'zone_name' => $this->zone?->zone_name,
            'aisle' => $this->aisle,
            'bay' => $this->bay,
            'level' => $this->level,
            'installation_date' => $this->installation_date,
            'last_inspection_date' => $this->last_inspection_date,
            'next_inspection_due_date' => $this->next_inspection_due_date,
            'inspection_frequency' => $this->inspection_frequency,
            'max_weight_capacity' => $this->max_weight_capacity,
            'max_volume_capacity' => $this->max_volume_capacity,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'material' => $this->material,
            'shelves_tiers_number' => $this->shelves_tiers_number,
            'adjustability' => $this->adjustability,
            'safety_features' => $this->safety_features,
            'load_type' => $this->load_type,
            'accessibility' => $this->accessibility,
            'uptime_percentage_method' => $this->uptime_percentage_method,
            'maintenance_cost' => $this->maintenance_cost,
            'currency_unit' => $this->currency_unit,
            'depreciation_start_date' => $this->depreciation_start_date,
            'depreciation_method' => $this->depreciation_method,
            'estimated_useful_life_year' => $this->estimated_useful_life_year,
            'supplier_id' => $this->supplier?->id,
            'supplier_code' => $this->supplier?->party_code,
            'supplier_name' => $this->supplier?->party_name,
            'expected_replacement_date' => $this->expected_replacement_date,
            'disposal_date' => $this->disposal_date,
            'replacement_mhe_code' => $this->replacement_mhe_code,
            'remark' => $this->remark,
            'custom_attributes' => $this->custom_attributes,
            'status' => $this->status ==1 ? 'Operational' : 'Under Maintenance',
            'status_value' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
