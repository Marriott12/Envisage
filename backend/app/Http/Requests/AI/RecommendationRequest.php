<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecommendationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Both guests and authenticated users can get recommendations
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'algorithm' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('ai.recommendations.algorithms', []))),
            ],
            'count' => 'nullable|integer|min:1|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'price_range' => 'nullable|array',
            'price_range.min' => 'nullable|numeric|min:0',
            'price_range.max' => 'nullable|numeric|gt:price_range.min',
            'exclude_products' => 'nullable|array|max:50',
            'exclude_products.*' => 'integer|exists:products,id',
            'context' => 'nullable|array',
            'context.product_id' => 'nullable|exists:products,id',
            'context.cart_items' => 'nullable|array',
            'context.recently_viewed' => 'nullable|array',
            'context.page_type' => 'nullable|string|in:home,product,cart,checkout,category',
            'diversify' => 'nullable|boolean',
            'include_trending' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'algorithm.in' => 'Invalid algorithm. Choose from: ' . implode(', ', array_keys(config('ai.recommendations.algorithms', []))),
            'count.max' => 'Maximum 100 recommendations can be requested.',
            'category_id.exists' => 'Selected category does not exist.',
            'price_range.max.gt' => 'Maximum price must be greater than minimum price.',
            'exclude_products.max' => 'Maximum 50 products can be excluded.',
            'exclude_products.*.exists' => 'One or more excluded product IDs are invalid.',
            'context.product_id.exists' => 'Context product does not exist.',
            'context.page_type.in' => 'Invalid page type.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Set defaults from config
        $this->merge([
            'count' => $this->count ?? 10,
            'diversify' => $this->diversify ?? true,
            'include_trending' => $this->include_trending ?? true,
        ]);
    }

    /**
     * Get validated data with sanitized inputs
     *
     * @return array
     */
    public function validated()
    {
        $validated = parent::validated();

        // Ensure integers are typed
        if (isset($validated['count'])) {
            $validated['count'] = (int) $validated['count'];
        }

        // Ensure booleans are typed
        $validated['diversify'] = (bool) ($validated['diversify'] ?? true);
        $validated['include_trending'] = (bool) ($validated['include_trending'] ?? true);

        // Ensure exclude_products is array of integers
        if (isset($validated['exclude_products'])) {
            $validated['exclude_products'] = array_map('intval', $validated['exclude_products']);
        }

        return $validated;
    }
}
