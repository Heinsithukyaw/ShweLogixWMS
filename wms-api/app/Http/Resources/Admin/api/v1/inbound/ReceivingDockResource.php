<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceivingDockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'receiving-docks.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'dock_code' => $this->dock_code,
            'dock_number' => $this->dock_number,
            'dock_type' => $this->dock_type,
            'zone_id' => $this->zone_id,
            'zone_code' => $this->zone?->zone_code,
            'zone_name' => $this->zone?->zone_name,
            'features' => $this->features,
            'available_features' => $this->available_features,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
