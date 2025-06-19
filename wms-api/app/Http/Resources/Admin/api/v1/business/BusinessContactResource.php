<?php

namespace App\Http\Resources\Admin\api\v1\business;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'business-contacts.destroy') {

            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'contact_code' => $this->contact_code,
            'contact_name'=> $this->contact_name,
            'business_party_id'=> $this->business_party_id,
            'business_party_code'=> $this->business_party?->party_code,
            'business_party_name'=> $this->business_party?->party_name,
            'designation'=> $this->designation,
            'department'=> $this->department,
            'phone_number'=> $this->phone_number,
            'email'=> $this->email,
            'address'=> $this->address,
            'country'=> $this->country,
            'preferred_contact_method'=> $this->preferred_contact_method,
            'status'=> $this->status,
            'notes' => $this->notes
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
