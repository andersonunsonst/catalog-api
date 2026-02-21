<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => 'required|string|max:100|unique:products,sku',
            'name' => 'required|string|min:3|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'category' => 'required|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'The SKU field is required',
            'sku.unique' => 'This SKU already exists',
            'name.required' => 'The product name is required',
            'name.min' => 'The product name must be at least 3 characters',
            'price.required' => 'The price is required',
            'price.min' => 'The price must be greater than 0',
            'category.required' => 'The category is required',
        ];
    }
}
