<?php

namespace App\Jobs;

use App\Mail\AbandonedCartMail;
use App\Models\AbandonedCart;
use App\Models\CartRecoveryEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $cart;
    public $emailType;

    public function __construct(AbandonedCart $cart, $emailType)
    {
        $this->cart = $cart;
        $this->emailType = $emailType;
    }

    public function handle()
    {
        // Check if cart is still abandoned
        if ($this->cart->recovered) {
            return;
        }

        // Generate discount code for later emails
        $discountCode = null;
        if ($this->emailType === '3_day') {
            $discountCode = $this->generateDiscountCode();
        }

        // Send email
        Mail::to($this->cart->user->email)->send(
            new AbandonedCartMail($this->cart, $this->emailType, $discountCode)
        );

        // Log email sent
        $recoveryEmail = CartRecoveryEmail::create([
            'abandoned_cart_id' => $this->cart->id,
            'email_type' => $this->emailType,
            'sent_at' => now(),
            'discount_code' => $discountCode,
            'discount_amount' => $this->emailType === '3_day' ? 10 : null,
        ]);

        // Update cart email count
        $this->cart->increment('recovery_email_count');

        // Schedule next email
        $this->scheduleNextEmail();
    }

    protected function generateDiscountCode()
    {
        return 'COMEBACK' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    protected function scheduleNextEmail()
    {
        $nextEmail = match($this->emailType) {
            '1_hour' => ['type' => '24_hour', 'delay' => now()->addHours(23)],
            '24_hour' => ['type' => '3_day', 'delay' => now()->addDays(2)],
            default => null,
        };

        if ($nextEmail) {
            SendAbandonedCartEmailJob::dispatch($this->cart, $nextEmail['type'])
                ->delay($nextEmail['delay']);
        }
    }
}
