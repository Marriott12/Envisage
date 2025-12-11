@component('mail::message')
# Payment Received

Your payment has been successfully processed!

## Payment Details

**Transaction ID:** {{ $transactionId }}  
**Amount Paid:** K{{ number_format($amount, 2) }}  
**Payment Method:** {{ ucfirst($paymentMethod) }}  
**Date:** {{ $payment->created_at->format('F j, Y g:i A') }}

@component('mail::panel')
**Status:** ✓ Confirmed
@endcomponent

@if($payment->order)
Your order **#{{ $payment->order->order_number }}** is now being processed.

@component('mail::button', ['url' => env('FRONTEND_URL', 'http://localhost:3000') . '/orders/' . $payment->order->id])
View Order
@endcomponent
@endif

## Receipt

A detailed receipt has been attached to this email for your records.

If you have any questions about this payment, please contact our support team.

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent
