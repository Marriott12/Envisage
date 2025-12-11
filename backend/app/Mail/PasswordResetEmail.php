<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $resetUrl = env('FRONTEND_URL', 'http://localhost:3000') 
                    . '/reset-password?token=' . $this->token 
                    . '&email=' . urlencode($this->email);

        return $this->subject('Reset Your Password')
                    ->markdown('emails.password-reset')
                    ->with([
                        'resetUrl' => $resetUrl,
                    ]);
    }
}
