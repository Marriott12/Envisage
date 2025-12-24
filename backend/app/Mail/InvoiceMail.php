<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $email = $this->subject('Invoice ' . $this->invoice->invoice_number . ' from ' . config('app.name'))
            ->view('emails.invoice')
            ->with([
                'invoice' => $this->invoice,
                'downloadUrl' => Storage::disk('public')->url($this->invoice->pdf_path),
            ]);

        // Attach PDF
        if ($this->invoice->pdf_path && Storage::disk('public')->exists($this->invoice->pdf_path)) {
            $email->attach(Storage::disk('public')->path($this->invoice->pdf_path), [
                'as' => $this->invoice->invoice_number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $email;
    }
}
