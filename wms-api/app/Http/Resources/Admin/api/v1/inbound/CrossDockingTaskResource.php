<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class CrossDockingTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'cross-docking-tasks.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'cross_docking_task_code' => $this->cross_docking_task_code,
            'asn_id' => $this->asn_id,
            'asn_code' => $this->asn?->asn_code,
            'asn_detail_id' => $this->asn_detail_id,
            'asn_detail_code' => $this->asn_detail?->asn_detail_code,
            'item_id' => $this->item_id,
            'item_code' => $this->product?->product_code,
            'item_name' => $this->product?->product_name,
            'item_description' => $this->item_description,
            'qty' => $this->qty,
            'source_location_id' => $this->source_location_id,
            'source_location_code' => $this->source_location?->location_code,
            'source_location_name' => $this->source_location?->location_name,
            'destination_location_id' => $this->destination_location_id,
            'destination_location_code' => $this->destination_location?->location_code,
            'destination_location_name' => $this->destination_location?->location_name,
            'assigned_to_id' => $this->assigned_to_id,
            'assigned_to_code' => $this->assigned_emp?->employee_code,
            'assigned_to_name' => $this->assigned_emp?->employee_name,
            'created_date' => $this->created_date,
            'complete_time' => $this->complete_time,
            'start_time' => $this->start_time,
            'priority' => $this->priority,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
