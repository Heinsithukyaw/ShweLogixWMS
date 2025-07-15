<?php

namespace App\Http\Resources\Admin\api\v1\shipping;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingCarrierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'shipping-carriers.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'carrier_code' => $this->carrier_code,
            'carrier_name' => $this->carrier_name,
            'contact_person' => $this->contact_person,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'address' => $this->address,
            'country' => $this->country,
            'contract_details' => $this->contract_details,
            'payment_terms' => $this->payment_terms,
            'service_type' => $this->service_type,
            'tracking_url' => $this->tracking_url,
            'performance_rating' => $this->performance_rating,
            'capabilities' => $this->capabilities,
            'creation_date' => $this->created_at,
            'created_by' => $this->created_by,
            'last_modified_date' => $this->updated_at,
            'last_modified_by' => $this->last_modified_by,
            'status' => $this->status
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
