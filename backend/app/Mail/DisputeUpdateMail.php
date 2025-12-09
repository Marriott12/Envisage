<?php

namespace App\Mail;

use App\Models\OrderDispute;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DisputeUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $dispute;
    public $statusChanged;

    public function __construct(OrderDispute $dispute, $statusChanged = false)
    {
        $this->dispute = $dispute;
        $this->statusChanged = $statusChanged;
    }

    public function build()
    {
        $subject = $this->statusChanged 
            ? 'Dispute Status Updated #' . $this->dispute->id
            : 'New Dispute Created #' . $this->dispute->id;

        return $this->subject($subject)
            ->view('emails.dispute-update')
            ->with([
                'dispute' => $this->dispute,
                'disputeUrl' => config('app.frontend_url') . '/disputes/' . $this->dispute->id,
            ]);
    }
}
