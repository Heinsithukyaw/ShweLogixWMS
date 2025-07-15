<?php

namespace App\Http\Requests\Admin\api\v1\equipment;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePalletEquipmentRequest extends FormRequest
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
            'pallet_code' => 'required',
            'pallet_name' => 'required',
            'pallet_type' => 'required',
            'material' => 'required',
            'manufacturer' => 'nullable',
            'length' => 'nullable',
            'width' => 'nullable',
            'height' => 'nullable',
            'weight_capacity' => 'nullable',
            'empty_weight' => 'nullable',
            'condition' => 'nullable',
            'current_location' => 'nullable',
            'purchase_date' => 'nullable',
            'last_inspection_date' => 'nullable',
            'next_inspection_date' => 'nullable',
            'pooled_pallet' => 'nullable',
            'pool_provider' => 'nullable',
            'cost_per_unit' => 'nullable',
            'expected_lifespan_year' => 'nullable',
            'rfid_tag' => 'nullable',
            'barcode' => 'nullable',
            'currently_assigned' => 'nullable',
            'assigned_shipment' => 'nullable',
            'status' => 'nullable',
            'notes' => 'nullable',
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
