<?php

namespace App\Http\Requests\Admin\api\v1\inbound;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCrossDockingTaskRequest extends FormRequest
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
            'cross_docking_task_code' => 'required|string',
            'asn_id' => 'required',
            'asn_detail_id' => 'required',
            'item_id' => 'required',
            'item_description' => 'nullable',
            'qty' => 'nullable',
            'source_location_id' => 'required',
            'destination_location_id' => 'required',
            'outbound_shipment_id' => 'nullable',
            'assigned_to_id' => 'required',
            'priority' => 'required',
            'status' => 'required',
            'created_date' => 'nullable',
            'start_time' => 'nullable',
            'complete_time' => 'nullable',
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
