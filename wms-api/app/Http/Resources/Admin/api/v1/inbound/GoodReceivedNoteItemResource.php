<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodReceivedNoteItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'good-received-note-items.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'grn_id' => $this->grn_id,
            'grn_code' => $this->good_received_note?->grn_code,
            'product_id' => $this->product_id,
            'product_code' => $this->product?->product_code,
            'product_name' => $this->product?->product_name,
            'uom_id' => $this->uom_id,
            'uom_code' => $this->unit_of_measure?->party_code,
            'uom_name' => $this->unit_of_measure?->party_name,
            'expected_qty' => $this->expected_qty,
            'received_qty' => $this->received_qty,
            'location_id' => $this->location_id,
            'location_code' => $this->stagingLocation?->staging_location_code,
            'location_name' => $this->stagingLocation?->staging_location_name,
            'notes' => $this->notes,
            'condition_status' => $this->condition_status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
