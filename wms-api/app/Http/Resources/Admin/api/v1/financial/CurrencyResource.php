<?php

namespace App\Http\Resources\Admin\api\v1\financial;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'currencies.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'currency_code' => $this->currency_code,
            'currency_name' => $this->currency_name,
            'symbol' => $this->symbol,
            'country' => $this->country,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'decimal_places' => $this->decimal_places,
            'creation_date' => $this->creation_date,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
