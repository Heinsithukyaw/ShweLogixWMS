<?php

namespace App\Http\Requests\Admin\api\v1\shipping;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateShippingCarrierRequest extends FormRequest
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
            'carrier_code' => 'required',
            'carrier_name' => 'required',
            'contact_person' => 'required',
            'phone_number' => 'required',
            'email' => 'required',
            'address' => 'nullable',
            'country' => 'nullable',
            'contract_details' => 'nullable',
            'payment_terms' => 'nullable',
            'service_type' => 'nullable',
            'tracking_url' => 'nullable',
            'performance_rating' => 'nullable',
            'capabilities' => 'nullable',
            'created_by' => 'nullable',
            'status' => 'required|integer'
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
