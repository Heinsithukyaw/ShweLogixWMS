<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class StagingLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'staging-locations.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'staging_location_code' => $this->staging_location_code,
            'staging_location_name' => $this->staging_location_name,
            'type' => $this->type,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_code' => $this->warehouse?->warehouse_code,
            'warehouse_name' => $this->warehouse?->warehouse_name,
            'warehouse_type' => $this->warehouse?->warehouse_type,

            'area_id' => $this->area_id,
            'area_code' => $this->area?->area_code,
            'area_name' => $this->area?->area_name,
            'area_type' => $this->area?->area_type,

            'zone_id' => $this->zone_id,
            'zone_code' => $this->zone?->zone_code,
            'zone_name' => $this->zone?->zone_name,
            'zone_type' => $this->zone?->zone_type,

            'capacity' => $this->capacity,
            'description' => $this->description,
            'current_usage' => $this->current_usage,
            'description' => $this->description,
            'last_updated' => $this->last_updated,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
