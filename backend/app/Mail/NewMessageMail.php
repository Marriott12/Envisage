<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function build()
    {
        return $this->subject('New message from ' . $this->message->sender->name)
            ->view('emails.new-message')
            ->with([
                'message' => $this->message,
                'conversationUrl' => config('app.frontend_url') . '/messages/' . $this->message->conversation_id,
            ]);
    }
}
