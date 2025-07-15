<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodReceivedNoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'good-received-notes.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'grn_code' => $this->grn_code,
            'inbound_shipment_id' => $this->inbound_shipment_id,
            'inbound_shipment_code' => $this->inbound_shipment?->shipment_code,
            'purchase_order_id' => $this->purchase_order_id,
            'supplier_id' => $this->supplier_id,
            'supplier_code' => $this->supplier?->party_code,
            'supplier_name' => $this->supplier?->party_name,
            'received_date' => $this->received_date,
            'created_by' => $this->created_by,
            'created_by_code' => $this->created_emp?->employee_code,
            'created_by_name' => $this->created_emp?->employee_name,
            'approved_by' => $this->approved_by,
            'approved_by_code' => $this->approved_emp?->employee_code,
            'approved_by_name' => $this->approved_emp?->employee_name,
            'total_items' => $this->total_items,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
