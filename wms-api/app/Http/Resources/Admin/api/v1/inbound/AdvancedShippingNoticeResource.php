<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvancedShippingNoticeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'advanced-shipping-notices.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'asn_code' => $this->asn_code,
            'supplier_id' => $this->supplier_id,
            'supplier_code' => $this->supplier?->party_code,
            'supplier_name' => $this->supplier?->party_name,
            'purchase_order_id' => $this->purchase_order_id,
            'expected_arrival' => $this->expected_arrival,
            'carrier_id' => $this->carrier_id,
            'carrier_code' => $this->carrier?->carrier_code,
            'carrier_name' => $this->carrier?->carrier_name,
            'tracking_number' => $this->tracking_number,
            'total_items' => $this->total_items,
            'total_pallet' => $this->total_pallets,
            'notes' => $this->notes,
            'received_date' => $this->received_date,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
