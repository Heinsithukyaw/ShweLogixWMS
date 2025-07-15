<?php

namespace App\Http\Requests\Admin\api\v1\inbound;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateReceivingExceptionRequest extends FormRequest
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
            'exception_code' => 'required|string',
            'asn_id' => 'required|integer',
            'asn_detail_id' => 'required|integer',
            'exception_type' => 'required',
            'item_id' => 'required',
            'item_description' => 'nullable',
            'severity' => 'required',
            'status' => 'required',
            'reported_by_id' => 'nullable',
            'assigned_to_id' => 'nullable',
            'reported_date' => 'nullable',
            'resolved_date' => 'nullable',
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
