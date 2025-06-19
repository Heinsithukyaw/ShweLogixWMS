<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class PutAwayTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'put-away-tasks.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'put_away_task_code' => $this->put_away_task_code,
            'inbound_shipment_detail_id' => $this->inbound_shipment_detail_id,
            'inbound_shipment_detail_code' => $this->inbound_shipment_detail?->inbound_detail_code,
            'inbound_shipment_detail_name' => $this->inbound_shipment_detail?->inbound_detail_name,
            'assigned_to_id' => $this->assigned_to_id,
            'assigned_to_code' => $this->assigned_emp?->employee_code,
            'assigned_to_name' => $this->assigned_emp?->employee_name,
            'created_date' => $this->created_date,
            'due_date' => $this->due_date,
            'start_time' => $this->start_time,
            'complete_time' => $this->complete_time,
            'source_location_id' => $this->source_location_id,
            'source_location_code' => $this->source_location?->staging_location_code,
            'source_location_name' => $this->source_location?->staging_location_name,
            'source_location_type' => $this->source_location?->type,
            'destination_location_id' => $this->destination_location_id,
            'destination_location_code' => $this->destination_location?->location_code,
            'destination_location_name' => $this->destination_location?->location_name,
            'qty' => $this->qty,
            'priority' => $this->priority,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
