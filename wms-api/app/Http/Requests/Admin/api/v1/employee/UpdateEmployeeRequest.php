<?php

namespace App\Http\Requests\Admin\api\v1\employee;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateEmployeeRequest extends FormRequest
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
            'employee_code' => 'required|string',
            'employee_name' => 'required',
            'email' => 'required',
            'phone_number' => 'required',
            'dob' => 'nullable',
            'gender' => 'nullable',
            'nationality' => 'nullable',
            'address' => 'nullable',
            'department_id' => 'nullable',
            'job_title' => 'nullable',
            'employment_type' => 'nullable',
            'shift' => 'nullable',
            'hire_date' => 'nullable',
            'salary' => 'nullable',
            'currency' => 'nullable',
            'is_supervisor' => 'required',
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
