<?php

namespace App\Http\Requests\Admin\api\v1\outbound;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSalesOrderRequest extends FormRequest
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
            'order_number' => 'required|string|unique:sales_orders,order_number',
            'customer_id' => 'required|exists:business_parties,id',
            'order_date' => 'required|date',
            'ship_date' => 'nullable|date|after_or_equal:order_date',
            'status' => 'nullable|in:pending,allocated,picking,packed,shipped,cancelled',
            'total_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'shipping_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'shipment_method' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'created_by' => 'nullable|string',
            'last_modified_by' => 'nullable|string',
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