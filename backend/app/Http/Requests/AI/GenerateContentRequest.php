<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Only authenticated users can generate content
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'content_type' => [
                'required',
                'string',
                Rule::in(['product_description', 'email', 'blog_post', 'social_media']),
            ],
            'prompt' => 'required|string|min:10|max:500',
            'tone' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('ai.content_generation.tones', []))),
            ],
            'length' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('ai.content_generation.lengths', []))),
            ],
            'context' => 'nullable|array',
            'context.product_id' => 'nullable|exists:products,id',
            'context.keywords' => 'nullable|array|max:10',
            'context.keywords.*' => 'string|max:50',
            'context.target_audience' => 'nullable|string|max:200',
            'context.call_to_action' => 'nullable|string|max:100',
            'temperature' => 'nullable|numeric|min:0|max:2',
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
            'content_type.required' => 'Please specify the type of content to generate.',
            'content_type.in' => 'Invalid content type. Must be product_description, email, blog_post, or social_media.',
            'prompt.required' => 'Please provide a prompt for content generation.',
            'prompt.min' => 'Prompt must be at least 10 characters long.',
            'prompt.max' => 'Prompt cannot exceed 500 characters.',
            'tone.in' => 'Invalid tone. Choose from: ' . implode(', ', array_keys(config('ai.content_generation.tones', []))),
            'length.in' => 'Invalid length. Choose from: ' . implode(', ', array_keys(config('ai.content_generation.lengths', []))),
            'context.product_id.exists' => 'Referenced product does not exist.',
            'context.keywords.max' => 'Maximum 10 keywords allowed.',
            'temperature.max' => 'Temperature must be between 0 and 2.',
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
            'tone' => $this->tone ?? 'professional',
            'length' => $this->length ?? 'medium',
            'temperature' => $this->temperature ?? config('ai.openai.temperature', 0.7),
        ]);

        // Sanitize prompt
        if ($this->prompt) {
            $this->merge([
                'prompt' => trim($this->prompt),
            ]);
        }
    }

    /**
     * Get validated data with sanitized inputs
     *
     * @return array
     */
    public function validated()
    {
        $validated = parent::validated();

        // Strip HTML tags from prompt and context strings
        if (isset($validated['prompt'])) {
            $validated['prompt'] = strip_tags($validated['prompt']);
        }

        if (isset($validated['context']['target_audience'])) {
            $validated['context']['target_audience'] = strip_tags($validated['context']['target_audience']);
        }

        if (isset($validated['context']['call_to_action'])) {
            $validated['context']['call_to_action'] = strip_tags($validated['context']['call_to_action']);
        }

        // Ensure temperature is float
        if (isset($validated['temperature'])) {
            $validated['temperature'] = (float) $validated['temperature'];
        }

        return $validated;
    }
}
