<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShippingUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $trackingNumber;
    public $carrier;

    public function __construct(Order $order, $trackingNumber, $carrier = null)
    {
        $this->order = $order;
        $this->trackingNumber = $trackingNumber;
        $this->carrier = $carrier;
    }

    public function build()
    {
        return $this->subject('Your order has been shipped! #' . $this->order->order_number)
            ->view('emails.shipping-update')
            ->with([
                'order' => $this->order,
                'trackingNumber' => $this->trackingNumber,
                'carrier' => $this->carrier,
                'trackingUrl' => $this->getTrackingUrl(),
            ]);
    }

    protected function getTrackingUrl()
    {
        if ($this->carrier && $this->trackingNumber) {
            return match(strtolower($this->carrier)) {
                'ups' => "https://www.ups.com/track?tracknum={$this->trackingNumber}",
                'fedex' => "https://www.fedex.com/fedextrack/?tracknumbers={$this->trackingNumber}",
                'usps' => "https://tools.usps.com/go/TrackConfirmAction?tLabels={$this->trackingNumber}",
                'dhl' => "https://www.dhl.com/en/express/tracking.html?AWB={$this->trackingNumber}",
                default => null,
            };
        }
        return null;
    }
}
