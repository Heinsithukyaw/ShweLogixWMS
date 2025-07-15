<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceivingAppointmentResource extends JsonResource
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
            'appointment_code' => $this->appointment_code,
            'inbound_shipment_id' => $this->inbound_shipment_id,
            'inbound_shipment_code' => $this->inbound_shipment?->shipment_code,
            'supplier_id' => $this->supplier_id,
            'supplier_code' => $this->supplier?->party_code,
            'supplier_name' => $this->supplier?->party_name,
            'dock_id' => $this->dock_id,
            'dock_code' => $this->dock?->dock_code,
            'dock_name' => $this->dock?->dock_name,
            'purchase_order_id' => $this->purchase_order_id,
            'scheduled_date' => $this->scheduled_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'carrier_name' => $this->carrier_name,
            'driver_name' => $this->driver_name,
            'driver_phone_number' => $this->driver_phone_number,
            'trailer_number' => $this->trailer_number,
            'estimated_pallet' => $this->estimated_pallet,
            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'version_control' => $this->version_control,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
