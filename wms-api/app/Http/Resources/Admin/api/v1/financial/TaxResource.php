<?php

namespace App\Http\Resources\Admin\api\v1\financial;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'taxes.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'tax_code' => $this->tax_code,
            'tax_description' => $this->tax_description,
            'tax_type' => $this->tax_type,
            'tax_rate' => $this->tax_rate,
            'effective_date' => $this->effective_date,
            'tax_calculation_method' => $this->tax_calculation_method,
            'tax_authority' => $this->tax_authority,
            'notes' => $this->notes,
            'status' => $this->status,
        ];

    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
