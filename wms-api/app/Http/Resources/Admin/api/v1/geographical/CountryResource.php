<?php

namespace App\Http\Resources\Admin\api\v1\geographical;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'countries.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'country_code' => $this->country_code,
            'country_name' => $this->country_name,
            'country_code_3' => $this->country_code_3,
            'numeric_code' => $this->numeric_code,
            'currency_id' => $this->currency_id,
            'currency_code' => $this->currency?->currency_code,
            'currency_name' => $this->currency?->currency_name,
            'phone_code' => $this->phone_code,
            'capital' => $this->capital,
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
