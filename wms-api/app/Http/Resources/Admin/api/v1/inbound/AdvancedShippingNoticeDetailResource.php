<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvancedShippingNoticeDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'advanced-shipping-notice-details.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'asn_detail_code' => $this->asn_detail_code,
            'asn_id' => $this->asn_id,
            'asn_code' => $this->advanced_shipping_notice?->asn_code,
            'asn_name' => $this->advanced_shipping_notice?->asn_name,
            'item_id' => $this->item_id,
            'item_code' => $this->product?->item_id,
            'item_name' => $this->product?->item_id,
            'item_description' => $this->item_description,
            'expected_qty' => $this->expected_qty,
            'uom_id' => $this->uom_id,
            'uom_code' => $this->unit_of_measure?->uom_code,
            'uom_name' => $this->unit_of_measure?->uom_name,
            'lot_number' => $this->lot_number,
            'expiration_date' => $this->expiration_date,
            'received_qty' => $this->received_qty,
            'variance' => $this->variance,
            'status' => $this->status,
            'location_id' => $this->location_id,
            'location_code' => $this->zoneLocation?->zone_code,
            'location_name' => $this->zoneLocation?->zone_name,
            'location_type' => $this->zoneLocation?->zone_type,
            'pallet_id' => $this->pallet_id,
            'pallet_code' => $this->pallet_equipment?->pallet_id,
            'pallet_name' => $this->pallet_equipment?->pallet_id,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
