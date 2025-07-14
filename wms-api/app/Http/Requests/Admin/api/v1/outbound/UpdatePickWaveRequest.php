<?php

namespace App\Http\Requests\Admin\api\v1\outbound;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePickWaveRequest extends FormRequest
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
        $pickWaveId = $this->route('pick_wave');
        return [
            'wave_number' => 'nullable|string|unique:pick_waves,wave_number,' . $pickWaveId,
            'wave_date' => 'nullable|date',
            'status' => 'nullable|in:planned,released,picking,completed,cancelled',
            'total_orders' => 'nullable|integer|min:0',
            'total_items' => 'nullable|integer|min:0',
            'assigned_to' => 'nullable|exists:employees,id',
            'planned_start_time' => 'nullable|date',
            'actual_start_time' => 'nullable|date',
            'planned_completion_time' => 'nullable|date',
            'actual_completion_time' => 'nullable|date',
            'notes' => 'nullable|string',
            'pick_strategy' => 'nullable|in:discrete,batch,zone,cluster',
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