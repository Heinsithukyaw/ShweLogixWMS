<?php

namespace App\Http\Resources\Admin\api\v1\brand;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Admin\api\v1\category\CategoryResource;

class BrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'brands.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'brand_code' => $this->brand_code,
            'brand_name' => $this->brand_name,
            'category_id' => (int)$this->category_id,
            'subcategory_id' => (int)$this->subcategory_id,
            'description' => $this->description,
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'subcategory' => new CategoryResource($this->whenLoaded('subcategory')),
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
