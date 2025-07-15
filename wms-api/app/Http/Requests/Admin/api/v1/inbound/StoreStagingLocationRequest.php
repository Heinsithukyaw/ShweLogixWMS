<?php

namespace App\Http\Requests\Admin\api\v1\inbound;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStagingLocationRequest extends FormRequest
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
            'staging_location_code' => 'required|string|unique:staging_locations,staging_location_code',
            'staging_location_name' => 'required',
            'type' => 'required',
            'warehouse_id' => 'required',
            'area_id' => 'required',
            'zone_id' => 'required',
            'capacity' => 'nullable',
            'description' => 'nullable',
            'current_usage' => 'nullable',
            'last_updated' => 'nullable',
            'status' => 'required'
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
