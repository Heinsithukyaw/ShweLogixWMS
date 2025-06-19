<?php

namespace App\Http\Resources\Admin\api\v1\warehouse;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'warehouses.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' =>  $this->id,
            'warehouse_code' =>  $this->warehouse_code,
            'warehouse_name' =>  $this->warehouse_name,
            'warehouse_type' =>  $this->warehouse_type,
            'description' =>  $this->description,
            'address' =>  $this->address,
            'city' =>  $this->city,
            'state_region' =>  $this->state_region,
            'country' =>  $this->country,
            'postal_code' =>  $this->postal_code,
            'phone_number' =>  $this->phone_number,
            'email' =>  $this->email,
            'contact_person' =>  $this->contact_person,
            'manager_name' =>  $this->manager_name,
            'storage_capacity' =>  $this->storage_level,
            'operating_hours' =>  $this->operating_hours,
            'custom_attributes' => $this->custom_attributes,
            'status' =>  $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
