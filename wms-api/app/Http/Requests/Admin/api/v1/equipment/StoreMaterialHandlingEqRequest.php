<?php

namespace App\Http\Requests\Admin\api\v1\equipment;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreMaterialHandlingEqRequest extends FormRequest
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
            'mhe_code' => 'required',
            'mhe_name' => 'required',
            'mhe_type' => 'required',
            'manufacturer' => 'required',
            'model' => 'required',
            'serial_number' => 'required',
            'purchase_date' => 'required',
            'warranty_expire_date' => 'required',
            'capacity' => 'required',
            'capacity_unit' => 'required',
            'current_location_detail' => 'nullable',
            'home_location' => 'nullable',
            'shift_availability' => 'nullable',
            'operator_assigned' => 'nullable',
            'maintenance_schedule_type' => 'nullable',
            'maintenance_frequency' => 'nullable',
            'last_maintenance_date' => 'nullable',
            'last_service_type' => 'nullable',
            'last_maintenance_due_date' => 'nullable',
            'safety_inspection_due_date' => 'nullable',
            'safety_certification_expire_date' => 'nullable',
            'safety_features' => 'nullable',
            'uptime_percentage_monthly' => 'nullable',
            'maintenance_cost' => 'nullable',
            'currency' => 'nullable',
            'energy_consumption_per_hour' => 'nullable',
            'depreciation_start_date' => 'nullable',
            'depreciation_method' => 'nullable',
            'estimated_useful_life_year' => 'nullable',
            'supplier_id' => 'nullable',
            'supplier_contact_id' => 'nullable',
            'expected_replacement_date' => 'nullable',
            'disposal_date' => 'nullable',
            'replacement_mhe_id' => 'nullable',
            'remark' => 'nullable',
            'custom_attributes' => 'nullable',
            'usage_status' => 'nullable',
            'status' => 'nullable',
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
