<?php

namespace App\Http\Resources\Admin\api\v1\business;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessPartyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       $routeName = Route::currentRouteName();
        if ($routeName === 'business-parties.destroy') {

            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'party_code' => $this->party_code,
            'party_name' => $this->party_name,
            'party_type' => $this->party_type,
            'contact_person' => $this->contact_person,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'address' => $this->address,
            'country' => $this->country,
            'tax_vat' => $this->tax_vat,
            'business_registration_no' => $this->business_registration_no,
            'payment_terms' => $this->payment_terms,
            'credit_limit' => $this->credit_limit,
            'status' => $this->status,
            'custom_attributes' => $this->custom_attributes,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }

}
