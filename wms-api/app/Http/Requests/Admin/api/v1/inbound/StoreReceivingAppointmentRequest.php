<?php

namespace App\Http\Requests\Admin\api\v1\inbound;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReceivingAppointmentRequest extends FormRequest
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
            'appointment_code' => 'required|string|unique:receiving_appointments,appointment_code',
            'inbound_shipment_id' => 'required',
            'supplier_id' => 'required',
            'dock_id' => 'required',
            'purchase_order_id' => 'nullable',
            'scheduled_date'  => 'nullable',
            'start_time'  => 'nullable',
            'end_time'  => 'nullable',
            'status'  => 'nullable',
            'carrier_name'  => 'nullable',
            'driver_name'  => 'nullable',
            'driver_phone_number'  => 'nullable',
            'trailer_number'  => 'nullable',
            'estimated_pallet'  => 'nullable',
            'check_in_time'  => 'nullable',
            'check_out_time'  => 'nullable',
            'version_control'  => 'nullable',
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
