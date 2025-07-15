<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class InboundShipmentDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'inbound-shipment-details.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'inbound_detail_code' => $this->inbound_detail_code,
            'inbound_shipment_id' => $this->inbound_shipment_id,
            'inbound_shipment_code' => $this->inbound_shipment?->shipment_code,
            'inbound_shipment_name' => $this->inbound_shipment->shipment_name,
            'product_id' => $this->product_id,
            'product_code' => $this->product?->product_code,
            'product_name' => $this->product?->product_name,
            'purchase_order_number' => $this->purchase_order_number,
            'expected_qty' => $this->expected_qty,
            'received_qty' => $this->received_qty,
            'damaged_qty' => $this->damaged_qty,
            'lot_number' => $this->lot_number,
            'expiration_date' => $this->expiration_date,
            'location_id' => $this->location_id,
            'location_code' => $this->location?->location_code,
            'location_name' => $this->location?->location_name,
            'received_by' => $this->received_by,
            'received_date' => $this->received_date,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
