<?php

namespace App\Http\Resources\Admin\api\v1\financial;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class CostTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'financial-categories.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'cost_code' => $this->cost_code,
            'cost_name' => $this->cost_name,
            'cost_type' => $this->cost_type,
            'category_id' => $this->cost_category?->id,
            'category_code' => $this->cost_category?->category_code,
            'category_name' => $this->cost_category?->category_name,
            'subcategory_id' => $this->cost_subcategory?->id,
            'subcategory_code' => $this->cost_subcategory?->category_code,
            'subcategory_name' => $this->cost_subcategory?->category_name,
            'created_by' => $this->created_by,
            'modified_by' => $this->modified_by,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
