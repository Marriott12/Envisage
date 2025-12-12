<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignLog;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaign;
    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign, User $user)
    {
        $this->campaign = $campaign;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Create log entry
            $log = CampaignLog::create([
                'campaign_id' => $this->campaign->id,
                'user_id' => $this->user->id,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            // Render email with template variables
            $variables = [
                'user_name' => $this->user->name,
                'user_email' => $this->user->email,
                'unsubscribe_url' => url("/api/marketing/unsubscribe/{$log->id}"),
                'tracking_pixel' => url("/api/marketing/track/open/{$log->id}"),
            ];

            $subject = $this->campaign->template->subject;
            $body = $this->campaign->template->render($variables);

            // Send email
            Mail::raw($body, function ($message) use ($subject) {
                $message->to($this->user->email)
                    ->subject($subject)
                    ->html(true);
            });

            // Update campaign stats
            $this->campaign->increment('total_sent');

            Log::info("Campaign email sent", [
                'campaign_id' => $this->campaign->id,
                'user_id' => $this->user->id,
                'log_id' => $log->id,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send campaign email", [
                'campaign_id' => $this->campaign->id,
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);

            // Update log status
            if (isset($log)) {
                $log->update(['status' => 'bounced']);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("SendCampaignEmail job failed", [
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
