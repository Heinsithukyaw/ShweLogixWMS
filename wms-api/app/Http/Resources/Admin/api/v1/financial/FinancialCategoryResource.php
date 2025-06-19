<?php

namespace App\Http\Resources\Admin\api\v1\financial;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialCategoryResource extends JsonResource
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
            'category_code' => $this->category_code,
            'category_name' => $this->category_name,
            'parent_id' => $this->parent_id,
            'parent_category_code' => $this->parent_category?->category_code,
            'parent_category_name' => $this->parent_category?->category_name,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
