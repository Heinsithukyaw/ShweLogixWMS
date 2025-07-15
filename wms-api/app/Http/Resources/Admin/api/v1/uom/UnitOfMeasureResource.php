<?php

namespace App\Http\Resources\Admin\api\v1\uom;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitOfMeasureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'unit_of_measure.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'uom_code' => $this->uom_code,
            'uom_name' => $this->uom_name,
            'base_uom_id' => $this->base_uom_id,
            'conversion_factor' => $this->conversion_factor,
            'description' => $this->description,
            'status' => $this->status,
            'base_uom' => $this->baseUom ? [
                'id' => $this->baseUom->id,
                'short_code' => $this->baseUom->short_code,
                'name' => $this->baseUom->name,
            ] : null,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
