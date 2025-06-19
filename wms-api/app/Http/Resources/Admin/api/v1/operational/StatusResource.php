<?php

namespace App\Http\Resources\Admin\api\v1\operational;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'statuses.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'status_code' => $this->status_code,
            'status_name' => $this->status_name,
            'entity_type' => $this->entity_type,
            'category' => $this->category,
            'description' => $this->description,
            'creation_date' => $this->created_at,
            'created_by' => $this->created_by,
            'last_modified_date' => $this->updated_at,
            'modified_by' => $this->modified_by,
            'analytics_flag' => $this->analytics_flag,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
