<?php

namespace App\Http\Resources\Admin\api\v1\order;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'order-types.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'order_type_code' => $this->order_type_code,
            'order_type_name' => $this->order_type_name,
            'direction' => $this->direction,
            'priority_level' => $this->priority_level,
            'default_workflow' => $this->default_workflow,
            'description' => $this->description,
            'status' => $this->status
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
