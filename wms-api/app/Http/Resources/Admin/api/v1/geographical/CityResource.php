<?php

namespace App\Http\Resources\Admin\api\v1\geographical;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'cities.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'city_code' => $this->city_code,
            'city_name' => $this->city_name,
            'country_id' => $this->country_id,
            'country_code' => $this->country?->country_code,
            'country_name' => $this->country?->country_name,
            'state_id' => $this->state_id,
            'state_code' => $this->state?->state_code,
            'state_name' => $this->state?->state_name,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'creation_date' => $this->created_at,
            'created_by' => $this->created_by,
            'last_modified_date' => $this->updated_at,
            'modified_by' => $this->modified_by,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
