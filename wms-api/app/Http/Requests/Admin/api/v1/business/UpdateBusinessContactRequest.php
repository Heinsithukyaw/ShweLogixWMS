<?php

namespace App\Http\Requests\Admin\api\v1\business;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateBusinessContactRequest extends FormRequest
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
        'contact_code' => 'required',
        'contact_name'=> 'required',
        'business_party_id'=> 'required',
        'designation'=> 'required',
        'department'=> 'required',
        'phone_number'=> 'required',
        'email'=> 'required',
        'address'=> 'nullable',
        'country'=> 'nullable',
        'preferred_contact_method'=> 'required',
        'status'=> 'nullable',
        'notes' => 'nullable'
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
