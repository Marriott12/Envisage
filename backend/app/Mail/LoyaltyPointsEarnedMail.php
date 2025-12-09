<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoyaltyPointsEarnedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $pointsEarned;
    public $source;
    public $totalPoints;

    public function __construct(User $user, $pointsEarned, $source, $totalPoints)
    {
        $this->user = $user;
        $this->pointsEarned = $pointsEarned;
        $this->source = $source;
        $this->totalPoints = $totalPoints;
    }

    public function build()
    {
        return $this->subject('You earned ' . $this->pointsEarned . ' loyalty points!')
            ->view('emails.loyalty-points-earned')
            ->with([
                'user' => $this->user,
                'pointsEarned' => $this->pointsEarned,
                'source' => $this->source,
                'totalPoints' => $this->totalPoints,
                'loyaltyUrl' => config('app.frontend_url') . '/loyalty',
            ]);
    }
}
