<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FraudReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Only admins can review fraud alerts
        return auth()->check() && auth()->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'fraud_alert_id' => 'required|exists:fraud_alerts,id',
            'decision' => [
                'required',
                'string',
                Rule::in(['approve', 'block', 'flag_for_review', 'request_verification']),
            ],
            'notes' => 'nullable|string|max:1000',
            'notify_customer' => 'nullable|boolean',
            'refund_order' => 'nullable|boolean',
            'block_user' => 'nullable|boolean',
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
            'fraud_alert_id.required' => 'Fraud alert ID is required.',
            'fraud_alert_id.exists' => 'Invalid fraud alert ID.',
            'decision.required' => 'Please select a decision.',
            'decision.in' => 'Invalid decision. Must be approve, block, flag_for_review, or request_verification.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Set defaults
        $this->merge([
            'notify_customer' => $this->notify_customer ?? false,
            'refund_order' => $this->refund_order ?? false,
            'block_user' => $this->block_user ?? false,
        ]);

        // Sanitize notes
        if ($this->notes) {
            $this->merge([
                'notes' => trim($this->notes),
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

        // Strip HTML from notes
        if (isset($validated['notes'])) {
            $validated['notes'] = strip_tags($validated['notes']);
        }

        // Ensure booleans are typed
        $validated['notify_customer'] = (bool) ($validated['notify_customer'] ?? false);
        $validated['refund_order'] = (bool) ($validated['refund_order'] ?? false);
        $validated['block_user'] = (bool) ($validated['block_user'] ?? false);

        return $validated;
    }
}
