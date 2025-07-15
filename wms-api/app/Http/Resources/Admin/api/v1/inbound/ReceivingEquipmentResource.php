<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceivingEquipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'receiving-equipments.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'receiving_equipment_code' => $this->receiving_equipment_code,
            'receiving_equipment_name' => $this->receiving_equipment_name,
            'receiving_equipment_type' => $this->receiving_equipment_type,
            'assigned_to_id' => $this->assigned_to_id,
            'assigned_to_code' => $this->assigned_emp?->employee_code,
            'assigned_to_name' => $this->assigned_emp?->employee_name,
            'last_maintenance_date' => $this->last_maintenance_date,
            'notes' => $this->notes,
            'days_since_maintenance' => $this->days_since_maintenance,
            'version_control' => $this->version_control,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
