<?php

namespace App\Mail;

use App\Models\AbandonedCart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbandonedCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cart;
    public $discountCode;
    public $emailType;

    public function __construct(AbandonedCart $cart, $emailType, $discountCode = null)
    {
        $this->cart = $cart;
        $this->emailType = $emailType;
        $this->discountCode = $discountCode;
    }

    public function build()
    {
        $subject = $this->getSubjectByType();
        
        return $this->subject($subject)
            ->view('emails.abandoned-cart')
            ->with([
                'cart' => $this->cart,
                'discountCode' => $this->discountCode,
                'recoveryUrl' => config('app.frontend_url') . '/cart/recover/' . $this->cart->recovery_token,
            ]);
    }

    protected function getSubjectByType()
    {
        return match($this->emailType) {
            '1_hour' => 'You left items in your cart!',
            '24_hour' => 'Still interested? Your cart is waiting',
            '3_day' => 'Last chance! Complete your purchase with 10% off',
            default => 'Your cart is waiting',
        };
    }
}
