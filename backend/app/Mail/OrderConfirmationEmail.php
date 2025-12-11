<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Order Confirmation - #' . $this->order->order_number)
                    ->markdown('emails.order-confirmation')
                    ->with([
                        'orderNumber' => $this->order->order_number,
                        'orderTotal' => $this->order->total,
                        'orderUrl' => env('FRONTEND_URL', 'http://localhost:3000') . '/orders/' . $this->order->id,
                    ]);
    }
}
