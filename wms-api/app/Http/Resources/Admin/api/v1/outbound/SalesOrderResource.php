<?php

namespace App\Http\Resources\Admin\api\v1\outbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'sales-orders.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_id' => $this->customer_id,
            'customer_code' => $this->customer?->party_code,
            'customer_name' => $this->customer?->party_name,
            'order_date' => $this->order_date,
            'ship_date' => $this->ship_date,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'grand_total' => $this->grand_total,
            'notes' => $this->notes,
            'priority' => $this->priority,
            'shipment_method' => $this->shipment_method,
            'payment_terms' => $this->payment_terms,
            'created_by' => $this->created_by,
            'last_modified_by' => $this->last_modified_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
} 