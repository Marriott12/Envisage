<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class VisualSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Allow both authenticated users and guests (for better UX)
        // Rate limiting will handle abuse prevention
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $maxFileSize = config('ai.vision.max_file_size', 10240); // KB from config

        return [
            'image' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,webp',
                'max:' . $maxFileSize,
                'dimensions:min_width=100,min_height=100,max_width=4096,max_height=4096',
            ],
            'max_results' => 'nullable|integer|min:1|max:50',
            'similarity_threshold' => 'nullable|numeric|min:0|max:1',
            'category_filter' => 'nullable|exists:categories,id',
            'price_range' => 'nullable|array',
            'price_range.min' => 'nullable|numeric|min:0',
            'price_range.max' => 'nullable|numeric|gt:price_range.min',
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
            'image.required' => 'Please upload an image to search.',
            'image.file' => 'The uploaded file must be a valid image.',
            'image.mimes' => 'Image must be in JPEG, PNG, or WebP format.',
            'image.max' => 'Image size cannot exceed :max KB (approximately 10MB).',
            'image.dimensions' => 'Image dimensions must be between 100x100 and 4096x4096 pixels.',
            'max_results.max' => 'Maximum 50 results can be returned.',
            'similarity_threshold.min' => 'Similarity threshold must be between 0 and 1.',
            'category_filter.exists' => 'Selected category does not exist.',
            'price_range.max.gt' => 'Maximum price must be greater than minimum price.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Set defaults from config if not provided
        $this->merge([
            'max_results' => $this->max_results ?? 20,
            'similarity_threshold' => $this->similarity_threshold ?? config('ai.vision.similarity_threshold', 0.7),
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

        // Ensure numeric values are properly typed
        if (isset($validated['max_results'])) {
            $validated['max_results'] = (int) $validated['max_results'];
        }
        if (isset($validated['similarity_threshold'])) {
            $validated['similarity_threshold'] = (float) $validated['similarity_threshold'];
        }

        return $validated;
    }
}
