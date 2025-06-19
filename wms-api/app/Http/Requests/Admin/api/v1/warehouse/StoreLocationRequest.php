<?php

namespace App\Http\Requests\Admin\api\v1\warehouse;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLocationRequest extends FormRequest
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
            'location_code' => 'required|string|unique:locations,location_code',
            'location_name' => 'required',
            'location_type'  => 'required',
            'zone_id'  => 'required',
            'aisle'  => 'nullable',
            'row'  => 'nullable',
            'level'  => 'nullable',
            'bin'  => 'nullable',
            'capacity'  => 'required',
            'capacity_unit'  => 'required',
            'restrictions'  => 'nullable',
            'bar_code'  => 'nullable',
            'description'  => 'nullable',
            'status'  => 'required',
            'utilization' => 'nullable'
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
