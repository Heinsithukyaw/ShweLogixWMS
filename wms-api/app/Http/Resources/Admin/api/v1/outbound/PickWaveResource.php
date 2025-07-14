<?php

namespace App\Http\Resources\Admin\api\v1\outbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class PickWaveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'pick-waves.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'wave_number' => $this->wave_number,
            'wave_date' => $this->wave_date,
            'status' => $this->status,
            'total_orders' => $this->total_orders,
            'total_items' => $this->total_items,
            'assigned_to' => $this->assigned_to,
            'assigned_employee_name' => $this->assignedEmployee?->employee_name,
            'planned_start_time' => $this->planned_start_time,
            'actual_start_time' => $this->actual_start_time,
            'planned_completion_time' => $this->planned_completion_time,
            'actual_completion_time' => $this->actual_completion_time,
            'notes' => $this->notes,
            'pick_strategy' => $this->pick_strategy,
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