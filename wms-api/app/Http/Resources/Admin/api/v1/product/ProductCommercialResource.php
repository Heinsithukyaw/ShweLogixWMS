<?php

namespace App\Http\Resources\Admin\api\v1\product;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCommercialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'product-commercials.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_code' => $this->product?->product_code,
            'product_name' => $this->product?->product_name,
            'customer_code' => $this->customer_code,
            'bar_code' => $this->bar_code,
            'cost_price' => $this->cost_price,
            'standard_price' => $this->standard_price,
            'currency' => $this->currency,
            'discount' => $this->discount,
            'supplier_id' => $this->supplier?->id,
            'supplier_code' => $this->supplier?->party_code,
            'supplier_name' => $this->supplier?->party_name,
            'manufacturer' => $this->manufacturer,
            'country_code' => $this->country_code,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
