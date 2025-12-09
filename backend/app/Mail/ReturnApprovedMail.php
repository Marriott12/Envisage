<?php

namespace App\Mail;

use App\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReturnApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $returnRequest;

    public function __construct(ReturnRequest $returnRequest)
    {
        $this->returnRequest = $returnRequest;
    }

    public function build()
    {
        return $this->subject('Return Request Approved #' . $this->returnRequest->id)
            ->view('emails.return-approved')
            ->with([
                'returnRequest' => $this->returnRequest,
                'returnUrl' => config('app.frontend_url') . '/returns/' . $this->returnRequest->id,
            ]);
    }
}
