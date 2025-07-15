<?php

namespace App\Http\Resources\Admin\api\v1\financial;

use App\Utlis\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTermResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $routeName = Route::currentRouteName();
        if ($routeName === 'financial-categories.destroy') {
            return parent::toArray($request);
        }

        return [
            'id' => $this->id,
            'payment_term_code' => $this->payment_term_code,
            'payment_term_name' => $this->payment_term_name,
            'payment_type' => $this->payment_type,
            'payment_due_day' => $this->payment_due_day,
            'discount_percent' => $this->discount_percent,
            'discount_day' => $this->discount_day,
            'creation_date' => $this->created_at,
            'created_by' => $this->created_by,
            'modified_by' => $this->updated_at,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }

    public function with($request)
    {
        return Json::resource($request);
    }
}
