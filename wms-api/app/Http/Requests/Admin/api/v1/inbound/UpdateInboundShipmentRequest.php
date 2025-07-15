<?php

namespace App\Http\Requests\Admin\api\v1\inbound;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateInboundShipmentRequest extends FormRequest
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
            'shipment_code' => 'required|string',
            'supplier_id' => 'required',
            'carrier_id' => 'required',
            'staging_location_id' => 'nullable',
            'purchase_order_id' => 'nullable',
            'expected_arrival' => 'nullable',
            'actual_arrival' => 'nullable',
            'status' => 'nullable',
            'version_control' => 'nullable',
            'trailer_number' => 'nullable',
            'seal_number' => 'nullable',
            'total_pallets' => 'nullable',
            'total_weight' => 'nullable',
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
