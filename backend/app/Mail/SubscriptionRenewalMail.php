<?php

namespace App\Mail;

use App\Models\SellerSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionRenewalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscription;
    public $daysUntilRenewal;

    public function __construct(SellerSubscription $subscription, $daysUntilRenewal)
    {
        $this->subscription = $subscription;
        $this->daysUntilRenewal = $daysUntilRenewal;
    }

    public function build()
    {
        return $this->subject('Your subscription renews in ' . $this->daysUntilRenewal . ' days')
            ->view('emails.subscription-renewal')
            ->with([
                'subscription' => $this->subscription,
                'subscriptionUrl' => config('app.frontend_url') . '/subscription',
            ]);
    }
}
