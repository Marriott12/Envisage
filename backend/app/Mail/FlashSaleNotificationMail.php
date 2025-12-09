<?php

namespace App\Mail;

use App\Models\FlashSale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FlashSaleNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $flashSale;

    public function __construct(FlashSale $flashSale)
    {
        $this->flashSale = $flashSale;
    }

    public function build()
    {
        return $this->subject('⚡ Flash Sale Alert: ' . $this->flashSale->name)
            ->view('emails.flash-sale-notification')
            ->with([
                'flashSale' => $this->flashSale,
                'saleUrl' => config('app.frontend_url') . '/flash-sales/' . $this->flashSale->id,
            ]);
    }
}
