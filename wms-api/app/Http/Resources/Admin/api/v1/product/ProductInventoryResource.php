<?php

namespace App\Http\Resources\Admin\api\v1\product;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\Admin\api\v1\product\ProductResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductInventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'product-inventories.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            // 'product' => new ProductResource($this->whenLoaded('product')),
            'product_id' => $this->product_id,
            'product_code' => $this->product?->product_code,
            'product_name' => $this->product?->product_name,
            'uom_id' => $this->unit_of_measure?->id,
            'uom_name' => $this->unit_of_measure?->uom_name,
            'warehouse_code' => $this->warehouse_code,
            'location' => $this->location,
            'reorder_level' => $this->reorder_level,
            'batch_no' => $this->batch_no,
            'lot_no' => $this->lot_no,
            'packing_qty' => $this->packing_qty,
            'whole_qty' => $this->whole_qty,
            'loose_qty' => $this->loose_qty,
            'stock_rotation_policy' => $this->stock_rotation_policy,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
