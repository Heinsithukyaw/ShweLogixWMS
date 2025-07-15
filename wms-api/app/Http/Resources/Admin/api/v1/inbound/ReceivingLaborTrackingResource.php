<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceivingLaborTrackingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'receiving-labor-trackings.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'labor_entry_code' => $this->labor_entry_code,
            'emp_id' => $this->emp_id,
            'emp_code' => $this->employee?->employee_code,
            'emp_name' => $this->employee?->employee_name,
            'inbound_shipment_id' => $this->inbound_shipment_id,
            'inbound_shipment_code' => $this->inbound_shipment?->shipment_code,
            'inbound_shipment_name' => $this->inbound_shipment?->shipment_name,
            'task_type' => $this->task_type,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_min' => $this->duration_min,
            'items_processed' => $this->items_processed,
            'pallets_processed' => $this->employee_code,
            'items_min' => $this->items_min,
            'notes' => $this->notes,
            'version_control' => $this->version_control,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
