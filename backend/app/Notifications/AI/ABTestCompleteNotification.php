<?php

namespace App\Notifications\AI;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ABTestCompleteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $experimentName;
    public $winningVariant;
    public $isStatisticallySignificant;
    public $liftPercentage;
    public $confidenceLevel;
    public $controlMetrics;
    public $treatmentMetrics;

    /**
     * Create a new notification instance.
     *
     * @param string $experimentName
     * @param string $winningVariant
     * @param bool $isStatisticallySignificant
     * @param float $liftPercentage
     * @param float $confidenceLevel
     * @param array $controlMetrics
     * @param array $treatmentMetrics
     * @return void
     */
    public function __construct($experimentName, $winningVariant, $isStatisticallySignificant, $liftPercentage, $confidenceLevel, $controlMetrics, $treatmentMetrics)
    {
        $this->experimentName = $experimentName;
        $this->winningVariant = $winningVariant;
        $this->isStatisticallySignificant = $isStatisticallySignificant;
        $this->liftPercentage = $liftPercentage;
        $this->confidenceLevel = $confidenceLevel;
        $this->controlMetrics = $controlMetrics;
        $this->treatmentMetrics = $treatmentMetrics;
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
        $significance = $this->isStatisticallySignificant ? '✅ Statistically Significant' : '⚠️ Not Significant';
        $liftDirection = $this->liftPercentage > 0 ? '📈' : '📉';

        return (new MailMessage)
            ->subject('A/B Test Complete: ' . $this->experimentName)
            ->greeting('A/B Test Results Available')
            ->line('Your A/B test **' . $this->experimentName . '** has been completed.')
            ->line('')
            ->line('**Winner:** ' . strtoupper($this->winningVariant))
            ->line('**Lift:** ' . $liftDirection . ' ' . number_format(abs($this->liftPercentage), 2) . '%')
            ->line('**Confidence:** ' . number_format($this->confidenceLevel * 100, 1) . '%')
            ->line('**Significance:** ' . $significance)
            ->line('')
            ->line('**Control Variant Performance:**')
            ->line('• Conversions: ' . ($this->controlMetrics['conversions'] ?? 0))
            ->line('• Conversion Rate: ' . number_format(($this->controlMetrics['conversion_rate'] ?? 0) * 100, 2) . '%')
            ->line('• Average Value: $' . number_format($this->controlMetrics['avg_value'] ?? 0, 2))
            ->line('')
            ->line('**Treatment Variant Performance:**')
            ->line('• Conversions: ' . ($this->treatmentMetrics['conversions'] ?? 0))
            ->line('• Conversion Rate: ' . number_format(($this->treatmentMetrics['conversion_rate'] ?? 0) * 100, 2) . '%')
            ->line('• Average Value: $' . number_format($this->treatmentMetrics['avg_value'] ?? 0, 2))
            ->action('View Full Results', url('/admin/ai/abtest/' . urlencode($this->experimentName)))
            ->line($this->isStatisticallySignificant 
                ? 'The results are statistically significant. Consider implementing the winning variant.'
                : 'The results are not statistically significant. Consider running the test longer.');
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
            'type' => 'abtest_complete',
            'experiment_name' => $this->experimentName,
            'winning_variant' => $this->winningVariant,
            'is_statistically_significant' => $this->isStatisticallySignificant,
            'lift_percentage' => $this->liftPercentage,
            'confidence_level' => $this->confidenceLevel,
            'control_metrics' => $this->controlMetrics,
            'treatment_metrics' => $this->treatmentMetrics,
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
            'type' => 'abtest_complete',
            'experiment_name' => $this->experimentName,
            'winning_variant' => $this->winningVariant,
            'is_statistically_significant' => $this->isStatisticallySignificant,
            'lift_percentage' => $this->liftPercentage,
            'message' => "A/B test '{$this->experimentName}' complete - Winner: {$this->winningVariant}",
            'timestamp' => now()->toISOString(),
        ]);
    }
}
