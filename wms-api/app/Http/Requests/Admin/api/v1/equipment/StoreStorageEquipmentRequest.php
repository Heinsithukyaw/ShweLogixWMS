<?php

namespace App\Http\Requests\Admin\api\v1\equipment;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStorageEquipmentRequest extends FormRequest
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
            'storage_equipment_code' => 'required',
            'storage_equipment_name' => 'required',
            'storage_equipment_type' => 'required',
            'manufacturer' => 'required',
            'model' => 'required',
            'serial_number' => 'required',
            'purchase_date' => 'required',
            'warranty_expire_date' => 'required',
            // 'zone_id' => 'required',
            'aisle' => 'nullable',
            'bay' => 'nullable',
            'level' => 'nullable',
            'installation_date' => 'nullable',
            'last_inspection_date' => 'nullable',
            'next_inspection_due_date' => 'nullable',
            'inspection_frequency' => 'nullable',
            'max_weight_capacity' => 'nullable',
            'max_volume_capacity' => 'nullable',
            'length' => 'nullable',
            'width' => 'nullable',
            'height' => 'nullable',
            'material' => 'nullable',
            'number_of_shelves_tiers' => 'nullable',
            'adjustability' => 'nullable',
            'safety_features' => 'nullable',
            'load_type' => 'nullable',
            'accessibility' => 'nullable',
            'uptime_percentage_method' => 'nullable',
            'maintenance_cost' => 'nullable',
            'currency_unit' => 'nullable',
            'depreciation_start_date' => 'nullable',
            'depreciation_method' => 'nullable',
            'estimated_useful_life_year' => 'nullable',
            'supplier_id' => 'nullable',
            'expected_replacement_date' => 'nullable',
            'disposal_date' => 'nullable',
            'replacement_mhe_code' => 'nullable',
            'remark' => 'nullable',
            'custom_attributes' => 'nullable',
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
