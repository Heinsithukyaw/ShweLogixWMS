<?php

namespace App\Http\Resources\Admin\api\v1\warehouse;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'zones.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' =>  $this->id,
            'zone_code' =>  $this->zone_code,
            'zone_name' =>  $this->zone_name,
            'zone_type' =>  $this->zone_type,
            'area_id' =>  $this->area?->id,
            'area_code' =>  $this->area?->area_code,
            'area_name' =>  $this->area?->area_name,
            'area_type' =>  $this->area?->area_type,
            'priority' => $this->priority,
            'description' =>  $this->description,
            'utilization' => round($this->locations->avg('utilization') ?? 0),
            'status' =>  $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
