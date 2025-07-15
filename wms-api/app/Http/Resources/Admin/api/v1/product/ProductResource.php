<?php

namespace App\Http\Resources\Admin\api\v1\product;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Admin\api\v1\category\CategoryResource;
use App\Http\Resources\Admin\api\v1\brand\BrandResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'products.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'product_code' => $this->product_code,
            'product_name' => $this->product_name,
            'category_id' => (int)$this->category_id,
            'subcategory_id' => (int)$this->subcategory_id,
            'brand_id' => (int)$this->brand_id,
            'part_no' => $this->part_no,
            'description' => $this->description,
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'subcategory' => new CategoryResource($this->whenLoaded('subcategory')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
        ];
    }
    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return Json::resource($request);
    }
}
