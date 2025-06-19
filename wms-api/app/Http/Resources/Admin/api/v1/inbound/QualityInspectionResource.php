<?php

namespace App\Http\Resources\Admin\api\v1\inbound;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class QualityInspectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'quality-inspections.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'quality_inspection_code' => $this->quality_inspection_code,
            'inbound_shipment_detail_id' => $this->inbound_shipment_detail_id,
            'inbound_shipment_detail_code' => $this->inbound_shipment_detail?->inbound_detail_code,
            'inspector_name' => $this->inspector_name,
            'rejection_reason' => $this->rejection_reason,
            'sample_size' => $this->carrier?->sample_size,
            'corrective_action' => $this->carrier?->corrective_action,
            'notes' => $this->notes,
            'image' => $this->image_path? Storage::disk('public')->url($this->image_path): null,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
