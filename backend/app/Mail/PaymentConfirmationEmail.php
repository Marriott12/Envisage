<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Payment Received - ' . $this->payment->transaction_id)
                    ->markdown('emails.payment-confirmation')
                    ->with([
                        'transactionId' => $this->payment->transaction_id,
                        'amount' => $this->payment->amount,
                        'paymentMethod' => $this->payment->payment_method,
                    ]);
    }
}
