<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Rate limiting handles authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'message' => 'required|string|min:1|max:1000',
            'conversation_id' => 'nullable|string|uuid',
            'intent' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('ai.chatbot.intents', []))),
            ],
            'context' => 'nullable|array',
            'context.product_id' => 'nullable|exists:products,id',
            'context.order_id' => 'nullable|exists:orders,id',
            'context.page' => 'nullable|string|max:100',
            'attachments' => 'nullable|array|max:3',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
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
            'message.required' => 'Please enter a message.',
            'message.max' => 'Message cannot exceed 1000 characters.',
            'conversation_id.uuid' => 'Invalid conversation ID format.',
            'intent.in' => 'Invalid intent specified.',
            'context.product_id.exists' => 'Referenced product does not exist.',
            'context.order_id.exists' => 'Referenced order does not exist.',
            'attachments.max' => 'Maximum 3 attachments allowed.',
            'attachments.*.mimes' => 'Attachments must be PDF, JPEG, or PNG files.',
            'attachments.*.max' => 'Each attachment cannot exceed 5MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Generate conversation ID if not provided
        if (!$this->conversation_id) {
            $this->merge([
                'conversation_id' => (string) \Illuminate\Support\Str::uuid(),
            ]);
        }

        // Sanitize message - remove excessive whitespace
        if ($this->message) {
            $this->merge([
                'message' => trim(preg_replace('/\s+/', ' ', $this->message)),
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

        // Strip HTML tags from message for security
        if (isset($validated['message'])) {
            $validated['message'] = strip_tags($validated['message']);
        }

        return $validated;
    }
}
