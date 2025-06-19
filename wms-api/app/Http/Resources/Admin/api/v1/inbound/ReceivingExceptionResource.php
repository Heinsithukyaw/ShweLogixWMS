<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceivingExceptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'receiving-exceptions.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'exception_code' => $this->exception_code,
            'asn_id' => $this->asn_id,
            'asn_code' => $this->asn?->asn_code,
            'asn_detail_id' => $this->asn_detail_id,
            'asn_detail_code' => $this->asn_detail?->asn_detail_code,
            'item_id' => $this->item_id,
            'item_code' => $this->product?->product_code,
            'item_name' => $this->product?->product_name,
            'item_description' => $this->item_description,
            'exception_type' => $this->exception_type,
            'severity' => $this->severity,
            'reported_by_id' => $this->reported_by_id,
            'reported_by_code' => $this->reported_emp?->employee_code,
            'reported_by_name' => $this->reported_emp?->employee_name,
            'assigned_to_id' => $this->assigned_to_id,
            'assigned_to_code' => $this->assigned_emp?->employee_code,
            'assigned_to_name' => $this->assigned_emp?->employee_name,
            'reported_date' => $this->reported_date,
            'resolved_date' => $this->resolved_date,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
