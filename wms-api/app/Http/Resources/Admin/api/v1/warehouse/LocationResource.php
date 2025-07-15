<?php

namespace App\Http\Resources\Admin\api\v1\warehouse;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'locations.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' =>  $this->id,
            'location_code' =>  $this->location_code,
            'location_name' =>  $this->location_name,
            'location_type' =>  $this->location_type,
            'zone_id' =>  $this->zone?->id,
            'zone_code' =>  $this->zone?->zone_code,
            'zone_name' =>  $this->zone?->zone_name,
            'zone_type' =>  $this->zone?->zone_type,
            'area_code' => $this->zone?->area?->area_code,
            'area_name' => $this->zone?->area?->area_name,
            'area_type' => $this->zone?->area?->area_type,
            'aisle' =>  $this->aisle,
            'row' =>  $this->row,
            'level' =>  $this->level,
            'bin' =>  $this->bin,
            'capacity' =>  $this->capacity,
            'capacity_unit' =>  $this->capacity_unit,
            'restrictions' =>  $this->restrictions,
            'bar_code' =>  $this->bar_code,
            'description' =>  $this->description,
            'utilization' => $this->utilization,
            'status_value' =>  $this->status,
            'status' =>  $this->status == 1? 'Available':($this->status == 2? 'Occupied' : ($this->status == 3? 'Reserved' : ($this->status == 4 ? 'Under Maintenance':''))),
        ];
    }

     public function with($request)
    {
        return Json::resource($request);
    }
}
