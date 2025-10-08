<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|min:3',
            'description' => 'required|string|max:5000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'image' => 'nullable|string|max:500',
            'stock' => 'nullable|integer|min:0',
            'category' => 'required|in:electronics,clothing,home,sports,books,automotive,other',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'name.min' => 'Product name must be at least 3 characters',
            'price.required' => 'Price is required',
            'price.numeric' => 'Price must be a valid number',
            'price.min' => 'Price cannot be negative',
            'category.required' => 'Please select a category',
            'category.in' => 'Invalid category selected',
        ];
    }
}
