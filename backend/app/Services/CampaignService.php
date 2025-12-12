<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\User;
use App\Jobs\SendCampaignEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    /**
     * Send campaign to target audience
     */
    public function sendCampaign(Campaign $campaign)
    {
        if ($campaign->status === 'completed') {
            throw new \Exception('Campaign already sent');
        }

        // Get target users
        $users = $this->getTargetUsers($campaign->target_audience);

        if ($users->isEmpty()) {
            throw new \Exception('No users match the target audience criteria');
        }

        // Update campaign status
        $campaign->update(['status' => 'active']);

        // Dispatch jobs for each user
        foreach ($users as $user) {
            SendCampaignEmail::dispatch($campaign, $user);
        }

        Log::info("Campaign sending initiated", [
            'campaign_id' => $campaign->id,
            'total_recipients' => $users->count(),
        ]);

        return $users->count();
    }

    /**
     * Get users matching target audience criteria
     */
    public function getTargetUsers($audience)
    {
        $query = User::query();

        if (empty($audience)) {
            return $query->where('email_verified_at', '!=', null)->get();
        }

        // Apply filters
        if (isset($audience['role'])) {
            $query->where('role', $audience['role']);
        }

        if (isset($audience['created_after'])) {
            $query->where('created_at', '>=', $audience['created_after']);
        }

        if (isset($audience['created_before'])) {
            $query->where('created_at', '<=', $audience['created_before']);
        }

        if (isset($audience['has_purchased'])) {
            if ($audience['has_purchased']) {
                $query->has('orders');
            } else {
                $query->doesntHave('orders');
            }
        }

        if (isset($audience['total_orders_min'])) {
            $query->has('orders', '>=', $audience['total_orders_min']);
        }

        if (isset($audience['total_spent_min'])) {
            $query->whereHas('orders', function ($q) use ($audience) {
                $q->selectRaw('SUM(total) as total_spent')
                    ->having('total_spent', '>=', $audience['total_spent_min']);
            });
        }

        if (isset($audience['last_purchase_days'])) {
            $query->whereHas('orders', function ($q) use ($audience) {
                $q->where('created_at', '>=', now()->subDays($audience['last_purchase_days']));
            });
        }

        // Email verification filter
        $query->whereNotNull('email_verified_at');

        return $query->get();
    }

    /**
     * Get campaign performance metrics
     */
    public function getCampaignMetrics(Campaign $campaign)
    {
        $logs = $campaign->logs;

        $metrics = [
            'total_sent' => $logs->count(),
            'opened' => $logs->where('status', 'opened')->count(),
            'clicked' => $logs->where('status', 'clicked')->count(),
            'bounced' => $logs->where('status', 'bounced')->count(),
            'unsubscribed' => $logs->where('status', 'unsubscribed')->count(),
            'open_rate' => 0,
            'click_rate' => 0,
            'bounce_rate' => 0,
            'unsubscribe_rate' => 0,
        ];

        if ($metrics['total_sent'] > 0) {
            $metrics['open_rate'] = round(($metrics['opened'] / $metrics['total_sent']) * 100, 2);
            $metrics['click_rate'] = round(($metrics['clicked'] / $metrics['total_sent']) * 100, 2);
            $metrics['bounce_rate'] = round(($metrics['bounced'] / $metrics['total_sent']) * 100, 2);
            $metrics['unsubscribe_rate'] = round(($metrics['unsubscribed'] / $metrics['total_sent']) * 100, 2);
        }

        // Timeline data
        $metrics['timeline'] = $logs->groupBy(function ($log) {
            return $log->created_at->format('Y-m-d');
        })->map(function ($group) {
            return $group->count();
        })->toArray();

        // Device breakdown
        $metrics['devices'] = $logs->groupBy(function ($log) {
            $agent = $log->user_agent ?? '';
            if (stripos($agent, 'mobile') !== false) return 'Mobile';
            if (stripos($agent, 'tablet') !== false) return 'Tablet';
            return 'Desktop';
        })->map(function ($group) {
            return $group->count();
        })->toArray();

        return $metrics;
    }

    /**
     * Pause an active campaign
     */
    public function pauseCampaign(Campaign $campaign)
    {
        if ($campaign->status !== 'active') {
            throw new \Exception('Only active campaigns can be paused');
        }

        $campaign->update(['status' => 'paused']);

        Log::info("Campaign paused", ['campaign_id' => $campaign->id]);
    }

    /**
     * Resume a paused campaign
     */
    public function resumeCampaign(Campaign $campaign)
    {
        if ($campaign->status !== 'paused') {
            throw new \Exception('Only paused campaigns can be resumed');
        }

        $campaign->update(['status' => 'active']);

        Log::info("Campaign resumed", ['campaign_id' => $campaign->id]);
    }
}
