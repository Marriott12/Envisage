<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'payment_method' => 'required|string|in:stripe,cash_on_delivery,mobile_money',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:255',
            'shipping_address.phone' => 'required|string|max:20',
            'shipping_address.address' => 'required|string|max:500',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.country' => 'required|string|max:100',
            'shipping_address.postal_code' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'payment_intent_id' => 'required_if:payment_method,stripe|string',
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
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Invalid payment method selected.',
            'shipping_address.required' => 'Shipping address is required.',
            'shipping_address.name.required' => 'Recipient name is required.',
            'shipping_address.phone.required' => 'Contact phone number is required.',
            'shipping_address.address.required' => 'Street address is required.',
            'shipping_address.city.required' => 'City is required.',
            'shipping_address.country.required' => 'Country is required.',
            'payment_intent_id.required_if' => 'Payment confirmation is required for card payments.',
        ];
    }
}
