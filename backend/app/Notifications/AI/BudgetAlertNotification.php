<?php

namespace App\Notifications\AI;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class BudgetAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $service;
    public $currentSpend;
    public $budgetLimit;
    public $percentageUsed;
    public $alertLevel;

    /**
     * Create a new notification instance.
     *
     * @param string $service
     * @param float $currentSpend
     * @param float $budgetLimit
     * @param float $percentageUsed
     * @param string $alertLevel (warning|critical)
     * @return void
     */
    public function __construct($service, $currentSpend, $budgetLimit, $percentageUsed, $alertLevel = 'warning')
    {
        $this->service = $service;
        $this->currentSpend = $currentSpend;
        $this->budgetLimit = $budgetLimit;
        $this->percentageUsed = $percentageUsed;
        $this->alertLevel = $alertLevel;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $subject = $this->alertLevel === 'critical' 
            ? '🚨 Critical AI Budget Alert - Action Required'
            : '⚠️ AI Budget Warning';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your AI service budget has reached **' . number_format($this->percentageUsed, 1) . '%** of the limit.')
            ->line('**Service:** ' . strtoupper($this->service))
            ->line('**Current Spend:** $' . number_format($this->currentSpend, 2))
            ->line('**Budget Limit:** $' . number_format($this->budgetLimit, 2))
            ->line('**Remaining:** $' . number_format($this->budgetLimit - $this->currentSpend, 2))
            ->action('View AI Analytics', url('/admin/ai/analytics'))
            ->line('Consider optimizing your AI usage or increasing the budget limit.');
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
            'type' => 'budget_alert',
            'service' => $this->service,
            'current_spend' => $this->currentSpend,
            'budget_limit' => $this->budgetLimit,
            'percentage_used' => $this->percentageUsed,
            'alert_level' => $this->alertLevel,
            'remaining' => $this->budgetLimit - $this->currentSpend,
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
            'type' => 'budget_alert',
            'service' => $this->service,
            'current_spend' => $this->currentSpend,
            'budget_limit' => $this->budgetLimit,
            'percentage_used' => $this->percentageUsed,
            'alert_level' => $this->alertLevel,
            'message' => "AI budget at {$this->percentageUsed}% for {$this->service}",
            'timestamp' => now()->toISOString(),
        ]);
    }
}
