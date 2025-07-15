<?php

namespace App\Http\Resources\Admin\api\v1\equipment;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class PalletEquipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'pallet-equipments.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'pallet_code' => $this->pallet_code,
            'pallet_name' => $this->pallet_name,
            'pallet_type' => $this->pallet_type,
            'material' => $this->material,
            'manufacturer' => $this->manufacturer,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'weight_capacity' => $this->weight_capacity,
            'empty_weight' => $this->empty_weight,
            'condition' => $this->condition,
            'current_location' => $this->current_location,
            'purchase_date' => $this->purchase_date,
            'last_inspection_date' => $this->last_inspection_date,
            'next_inspection_date' => $this->next_inspection_date,
            'pooled_pallet' => $this->pooled_pallet,
            'pool_provider' => $this->pool_provider,
            'cost_per_unit' => $this->cost_per_unit,
            'expected_lifespan_year' => $this->expected_lifespan_year,
            'rfid_tag' => $this->rfid_tag,
            'barcode' => $this->barcode,
            'currently_assigned' => $this->currently_assigned,
            'assigned_shipment' => $this->assigned_shipment,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}


