<?php

namespace App\Http\Requests\Admin\api\v1\warehouse;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAreaRequest extends FormRequest
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
            'area_code' => 'required|string|unique:areas,area_code',
            'area_name' => 'required',
            'area_type' => 'required',
            'warehouse_id' => 'required',
            'responsible_person' => 'nullable',           
            'phone_number' => 'nullable',
            'email' => 'nullable',
            'location_description' => 'nullable',
            'capacity' => 'nullable',
            'dimensions' => 'nullable',
            'environmental_conditions' => 'nullable',
            'equipment' => 'nullable',
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
