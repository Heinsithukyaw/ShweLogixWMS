<?php

namespace App\Http\Resources\Admin\api\v1\product;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOtherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       $routeName = Route::currentRouteName();
        if ($routeName === 'product-others.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_code' => $this->product?->product_code,
            'product_name' => $this->product?->product_name,
            'manufacture_date' => $this->manufacture_date,
            'expire_date' => $this->expire_date,
            'abc_category_value' => $this->abc_category_value,
            'abc_category_activity' => $this->abc_category_activity,
            'remark' => $this->remark,
            'custom_attributes' => $this->custom_attributes,

        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
