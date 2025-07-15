<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Admin\api\v1\equipment\MaterialHandlingEqResource;

class UnloadingSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'receiving-appointments.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'unloading_session_code' => $this->unloading_session_code,
            'inbound_shipment_id' => $this->inbound_shipment_id,
            'inbound_shipment_code' => $this->inbound_shipment?->inbound_shipment_code,
            'inbound_shipment_name' => $this->inbound_shipment?->inbound_shipment_name,
            'supervisor_id' => $this->supervisor_id,
            'supervisor_code' => $this->employee?->employee_code,
            'supervisor_name' => $this->employee?->employee_name,
            'dock_id' => $this->dock_id,
            'dock_code' => $this->dock?->dock_code,
            'dock_name' => $this->dock?->dock_name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'total_pallets_unloaded' => $this->total_pallets_unloaded,
            'total_items_unloaded' => $this->total_items_unloaded,
            'equipment_used' => $this->equipment_used,
            'equipment_used_details' => MaterialHandlingEqResource::collection(
            \App\Models\MaterialHandlingEq::whereIn('id', json_decode($this->equipment_used ?? '[]'))->get()),
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
