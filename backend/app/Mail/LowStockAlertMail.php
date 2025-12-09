<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $product;
    public $currentStock;
    public $threshold;

    public function __construct(Product $product, $currentStock, $threshold)
    {
        $this->product = $product;
        $this->currentStock = $currentStock;
        $this->threshold = $threshold;
    }

    public function build()
    {
        return $this->subject('Low Stock Alert: ' . $this->product->name)
            ->view('emails.low-stock-alert')
            ->with([
                'product' => $this->product,
                'currentStock' => $this->currentStock,
                'threshold' => $this->threshold,
                'productUrl' => config('app.frontend_url') . '/seller/products/' . $this->product->id,
            ]);
    }
}
