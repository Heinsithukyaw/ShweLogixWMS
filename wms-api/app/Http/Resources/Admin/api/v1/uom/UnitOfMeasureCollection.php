<?php

namespace App\Http\Resources\Admin\api\v1\uom;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UnitOfMeasureCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
