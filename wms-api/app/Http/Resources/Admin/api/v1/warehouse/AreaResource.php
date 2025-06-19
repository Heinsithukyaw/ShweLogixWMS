<?php

namespace App\Http\Resources\Admin\api\v1\warehouse;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'areas.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' =>  $this->id,
            'area_code' =>  $this->area_code,
            'area_name' =>  $this->area_name,
            'area_type' =>  $this->area_type,
            'warehouse_id' =>  $this->warehouse?->id,
            'warehouse_code' => $this->warehouse?->warehouse_code,
            'warehouse_name' => $this->warehouse?->warehouse_name,
            'responsible_person' =>  $this->responsible_person,
            'phone_number' =>  $this->phone_number,
            'email' =>  $this->email,
            'location_description' =>  $this->location_description,
            'capacity' =>  $this->capacity,
            'dimensions' =>  $this->dimensions,
            'environmental_conditions' =>  $this->environmental_conditions,
            'equipment' =>  $this->equipment,
            'custom_attributes' => $this->custom_attributes,
            'status_value' =>  $this->status,
            'status' =>  $this->status == 0? 'In Active':($this->status == 1? 'Active' : ($this->status == 2? 'Under Maintenance' : ($this->status == 3 ? 'Planned':'Discommisioned'))),
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
