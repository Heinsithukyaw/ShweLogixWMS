<?php

namespace App\Http\Resources\Admin\api\v1\equipment;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class DockEquipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'dock-equipment-eqs.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'dock_code' => $this->dock_code,
            'dock_name' => $this->dock_name,
            'dock_type' => $this->dock_type,
            'warehouse_id' => $this->warehouse?->id,
            'warehouse_code' => $this->warehouse?->warehouse_code,
            'warehouse_name' => $this->warehouse?->warehouse_name,
            'area_id' => $this->area?->id,
            'area_code' => $this->area?->area_code,
            'area_name' => $this->area?->area_name,
            'dock_number' => $this->dock_number,
            'capacity' => $this->capacity,
            'capacity_unit' => $this->capacity_unit,
            'dimensions' => $this->dimensions,
            'equipment_features' => $this->equipment_features,
            'last_maintenance_date' => $this->last_maintenance_date,
            'next_maintenance_date' => $this->next_maintenance_date,
            'assigned_staff' => $this->assigned_staff,
            'operating_hours' => $this->operating_hours,
            'remarks' => $this->remarks,
            'custom_attributes' => $this->custom_attributes,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
