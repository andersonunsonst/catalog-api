<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('product');

        return [
            'sku' => 'sometimes|string|max:100|unique:products,sku,' . $productId,
            'name' => 'sometimes|string|min:3|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0.01',
            'category' => 'sometimes|string|max:100',
            'status' => 'sometimes|in:active,inactive',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU already exists',
            'name.min' => 'The product name must be at least 3 characters',
            'price.min' => 'The price must be greater than 0',
        ];
    }
}
