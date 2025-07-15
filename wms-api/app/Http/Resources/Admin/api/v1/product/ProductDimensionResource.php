<?php

namespace App\Http\Resources\Admin\api\v1\product;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDimensionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       $routeName = Route::currentRouteName();
        if ($routeName === 'product-dimensions.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_code' => $this->product?->product_code,
            'product_name' => $this->product?->product_name,
            'dimension_use' => $this->dimension_use,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'weight' => $this->weight,
            'volume' => $this->volume,
            'storage_volume' => $this->storage_volume,
            'space_area' => $this->space_area,
            'units_per_box' => $this->units_per_box,
            'boxes_per_pallet' => $this->boxes_per_pallet,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
