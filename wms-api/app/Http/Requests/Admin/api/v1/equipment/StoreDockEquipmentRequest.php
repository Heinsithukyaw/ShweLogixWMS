<?php

namespace App\Http\Requests\Admin\api\v1\equipment;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreDockEquipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dock_code' => 'required',
            'dock_name' => 'required',
            'dock_type' => 'required',
            'warehouse_id' => 'required',
            'area_id' => 'required',
            'dock_number' => 'required',
            'capacity' => 'required',
            'capacity_unit' => 'required',
            'dimensions' => 'nullable',
            'equipment_features' => 'nullable',
            'last_maintenance_date' => 'nullable',
            'next_maintenance_date' => 'nullable',
            'assigned_staff' => 'nullable',
            'operating_hours' => 'nullable',
            'remarks' => 'nullable',
            'custom_attributes' => 'nullable',
            'status' => 'required',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = [];
        foreach ($validator->errors()->getMessages() as $field => $messages) {
            $errors[$field] = $messages[0];
        }

        throw new HttpResponseException(response()->json([
            'message' => 'Validation Failed!.',
            'errors' => $errors,
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
