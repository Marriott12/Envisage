<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255|min:3',
            'description' => 'sometimes|required|string|max:5000',
            'price' => 'sometimes|required|numeric|min:0|max:999999.99',
            'image' => 'nullable|string|max:500',
            'stock' => 'nullable|integer|min:0',
            'category' => 'sometimes|required|in:electronics,clothing,home,sports,books,automotive,other',
            'status' => 'sometimes|in:active,inactive,sold',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'name.min' => 'Product name must be at least 3 characters',
            'price.required' => 'Price is required',
            'price.numeric' => 'Price must be a valid number',
            'category.in' => 'Invalid category selected',
            'status.in' => 'Invalid status',
        ];
    }
}
