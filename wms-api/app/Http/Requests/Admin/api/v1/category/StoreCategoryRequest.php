<?php

namespace App\Http\Requests\Admin\api\v1\category;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCategoryRequest extends FormRequest
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
            'category_code' => 'required|string|unique:categories,category_code',
            'category_name'  => 'required|string|max:255',
            'uom_id' => 'required|integer|exists:unit_of_measures,id',
            'parent_id' => 'nullable',
            'hierarchy_level' => 'required|integer',
            'applicable_industry' => 'required',
            'storage_condition' => 'required',
            'handling_instructions' => 'required',
            'tax_category' => 'required',
            'uom_id' => 'required',
            'description' => 'nullable',
            'status' => 'nullable',
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
