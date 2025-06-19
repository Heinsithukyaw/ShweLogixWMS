<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class InboundShipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'inbound-shipments.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'shipment_code' => $this->shipment_code,
            'supplier_id' => $this->supplier_id,
            'supplier_code' => $this->supplier?->party_code,
            'supplier_name' => $this->supplier?->party_name,
            'carrier_id' => $this->carrier_id,
            'carrier_code' => $this->carrier?->carrier_code,
            'carrier_name' => $this->carrier?->carrier_name,
            'staging_location_id' => $this->staging_location_id,
            'staging_location_code' => $this->stagingLocation?->staging_location_code,
            'staging_location_name' => $this->stagingLocation?->staging_location_name,
            'purchase_order_id' => $this->purchase_order_id,
            'expected_arrival' => $this->expected_arrival,
            'actual_arrival' => $this->actual_arrival,
            'version_control' => $this->version_control,
            'trailer_number' => $this->trailer_number,
            'seal_number' => $this->seal_number,
            'total_pallets' => $this->total_pallets,
            'total_weight' => $this->total_weight,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
