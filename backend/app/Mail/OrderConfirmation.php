<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->user = $order->user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Order Confirmation #' . $this->order->id)
                    ->view('emails.orders.confirmation')
                    ->with([
                        'orderNumber' => $this->order->id,
                        'orderDate' => $this->order->created_at->format('M d, Y'),
                        'totalAmount' => number_format($this->order->total_amount, 2),
                        'shippingAddress' => $this->order->shipping_address,
                        'items' => $this->order->items,
                        'orderUrl' => env('FRONTEND_URL') . '/orders/' . $this->order->id,
                    ]);
    }
}
