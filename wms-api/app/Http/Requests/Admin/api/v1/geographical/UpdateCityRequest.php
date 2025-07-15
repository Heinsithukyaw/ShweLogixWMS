<?php

namespace App\Http\Requests\Admin\api\v1\geographical;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCityRequest extends FormRequest
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
            'city_code' => 'required|string',
            'city_name' => 'required',
            'country_id' => 'required',
            'state_id' => 'required',
            'postal_code' => 'nullable',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'created_by' => 'nullable',
            'last_modified_by' => 'nullable',
            'status' => 'nullable'
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
