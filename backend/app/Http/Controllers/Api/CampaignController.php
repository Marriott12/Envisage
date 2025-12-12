<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    // Get all campaigns
    public function index(Request $request)
    {
        $query = Campaign::with('template');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $campaigns = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($campaigns);
    }

    // Get campaign details
    public function show($id)
    {
        $campaign = Campaign::with(['template', 'logs'])->findOrFail($id);

        $stats = [
            'total_sent' => $campaign->total_sent,
            'opened' => $campaign->opened,
            'clicked' => $campaign->clicked,
            'converted' => $campaign->converted,
            'open_rate' => $campaign->open_rate,
            'click_rate' => $campaign->click_rate,
            'conversion_rate' => $campaign->conversion_rate,
        ];

        return response()->json([
            'campaign' => $campaign,
            'stats' => $stats,
        ]);
    }

    // Create campaign
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,sms,push',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:email_templates,id',
            'target_audience' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
        ]);

        $campaign = Campaign::create($request->all());

        return response()->json([
            'message' => 'Campaign created successfully',
            'campaign' => $campaign,
        ], 201);
    }

    // Update campaign
    public function update(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->status === 'completed') {
            return response()->json(['error' => 'Cannot update completed campaign'], 400);
        }

        $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:email_templates,id',
            'target_audience' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
            'status' => 'in:draft,scheduled,active,completed,paused',
        ]);

        $campaign->update($request->all());

        return response()->json([
            'message' => 'Campaign updated successfully',
            'campaign' => $campaign,
        ]);
    }

    // Delete campaign
    public function destroy($id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->status === 'active') {
            return response()->json(['error' => 'Cannot delete active campaign'], 400);
        }

        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted successfully']);
    }

    // Send campaign
    public function send(Request $request, $id)
    {
        $campaign = Campaign::with('template')->findOrFail($id);

        if ($campaign->status === 'completed') {
            return response()->json(['error' => 'Campaign already sent'], 400);
        }

        // Get target users
        $users = $this->getTargetUsers($campaign->target_audience);

        DB::transaction(function () use ($campaign, $users) {
            foreach ($users as $user) {
                // Create log entry
                $log = CampaignLog::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $user->id,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                // TODO: Send actual email/SMS
                // Mail::to($user->email)->send(new CampaignEmail($campaign, $user));
            }

            // Update campaign stats
            $campaign->update([
                'status' => 'completed',
                'sent_at' => now(),
                'total_sent' => $users->count(),
            ]);
        });

        return response()->json([
            'message' => 'Campaign sent successfully',
            'total_sent' => $users->count(),
        ]);
    }

    // Track email open
    public function trackOpen(Request $request, $logId)
    {
        $log = CampaignLog::findOrFail($logId);

        if (!$log->opened_at) {
            $log->update([
                'status' => 'opened',
                'opened_at' => now(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ]);

            $log->campaign->increment('opened');
        }

        // Return 1x1 transparent pixel
        return response()->file(public_path('images/pixel.png'));
    }

    // Track email click
    public function trackClick(Request $request, $logId)
    {
        $log = CampaignLog::findOrFail($logId);

        if (!$log->clicked_at) {
            $log->update([
                'status' => 'clicked',
                'clicked_at' => now(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ]);

            $log->campaign->increment('clicked');
        }

        // Redirect to actual URL
        return redirect($request->get('url'));
    }

    // Get campaign analytics
    public function analytics($id)
    {
        $campaign = Campaign::with('logs')->findOrFail($id);

        $analytics = [
            'total_sent' => $campaign->total_sent,
            'opened' => $campaign->opened,
            'clicked' => $campaign->clicked,
            'converted' => $campaign->converted,
            'open_rate' => round($campaign->open_rate, 2),
            'click_rate' => round($campaign->click_rate, 2),
            'conversion_rate' => $campaign->conversion_rate,
            'bounced' => $campaign->logs()->where('status', 'bounced')->count(),
            'unsubscribed' => $campaign->logs()->where('status', 'unsubscribed')->count(),
            'timeline' => $campaign->logs()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json($analytics);
    }

    protected function getTargetUsers($audience)
    {
        $query = User::query();

        if (!empty($audience)) {
            // Apply audience filters
            if (isset($audience['role'])) {
                $query->where('role', $audience['role']);
            }

            if (isset($audience['created_after'])) {
                $query->where('created_at', '>=', $audience['created_after']);
            }

            // Add more filters as needed
        }

        return $query->get();
    }
}
