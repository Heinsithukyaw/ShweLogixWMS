<?php

namespace App\Http\Resources\Admin\api\v1\category;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Admin\api\v1\uom\UnitOfMeasureResource;
use App\Http\Resources\Admin\api\v1\category\CategoryResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'categories.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'category_code' => $this->category_code,
            'category_name' => $this->category_name,
            'parent_id' => $this->parent_id,
            'hierarchy_level' => $this->hierarchy_level,
            'applicable_industry' => $this->applicable_industry,
            'storage_condition' => $this->storage_condition,
            'handling_instructions' => $this->handling_instructions,
            'tax_category' => $this->tax_category,
            'uom_id' => $this->uom_id,
            'description' => $this->description,
            'status' => $this->status,
            'parent_category' => new CategoryResource($this->whenLoaded('parent_category')),
            'unit_of_measure' => new UnitOfMeasureResource($this->whenLoaded('unit_of_measure')),
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
