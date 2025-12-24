<?php

namespace App\Notifications\AI;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FraudDetectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $transactionId;
    public $riskScore;
    public $riskLevel;
    public $indicators;
    public $transactionAmount;
    public $customerEmail;

    /**
     * Create a new notification instance.
     *
     * @param int $transactionId
     * @param float $riskScore
     * @param string $riskLevel
     * @param array $indicators
     * @param float $transactionAmount
     * @param string $customerEmail
     * @return void
     */
    public function __construct($transactionId, $riskScore, $riskLevel, $indicators, $transactionAmount, $customerEmail)
    {
        $this->transactionId = $transactionId;
        $this->riskScore = $riskScore;
        $this->riskLevel = $riskLevel;
        $this->indicators = $indicators;
        $this->transactionAmount = $transactionAmount;
        $this->customerEmail = $customerEmail;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Send immediate alerts for high/critical risk
        if (in_array($this->riskLevel, ['high', 'critical'])) {
            return ['database', 'broadcast', 'mail'];
        }
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $emoji = $this->riskLevel === 'critical' ? '🚨' : '⚠️';
        $subject = "{$emoji} Fraud Alert - Transaction #{$this->transactionId}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Fraud Alert!')
            ->line('A potentially fraudulent transaction has been detected.')
            ->line('**Transaction ID:** #' . $this->transactionId)
            ->line('**Risk Score:** ' . number_format($this->riskScore, 2) . '/100')
            ->line('**Risk Level:** ' . strtoupper($this->riskLevel))
            ->line('**Amount:** $' . number_format($this->transactionAmount, 2))
            ->line('**Customer:** ' . $this->customerEmail)
            ->line('')
            ->line('**Fraud Indicators:**');

        foreach ($this->indicators as $indicator) {
            $mail->line('• ' . $indicator);
        }

        $mail->action('Review Transaction', url('/admin/fraud/review/' . $this->transactionId))
            ->line('Please review this transaction immediately.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'fraud_detected',
            'transaction_id' => $this->transactionId,
            'risk_score' => $this->riskScore,
            'risk_level' => $this->riskLevel,
            'indicators' => $this->indicators,
            'transaction_amount' => $this->transactionAmount,
            'customer_email' => $this->customerEmail,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'type' => 'fraud_detected',
            'transaction_id' => $this->transactionId,
            'risk_score' => $this->riskScore,
            'risk_level' => $this->riskLevel,
            'indicators' => $this->indicators,
            'message' => "Fraud detected: Transaction #{$this->transactionId} - {$this->riskLevel} risk",
            'timestamp' => now()->toISOString(),
        ]);
    }
}
