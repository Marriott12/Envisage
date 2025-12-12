<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticEvent;
use App\Models\UserSession;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    // Track an event
    public function trackEvent(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'event_type' => 'required|string',
            'event_category' => 'nullable|string',
            'event_action' => 'nullable|string',
            'event_label' => 'nullable|string',
            'properties' => 'nullable|array',
            'page_url' => 'nullable|string',
            'revenue' => 'nullable|numeric',
        ]);

        $event = AnalyticEvent::trackEvent(array_merge(
            $request->all(),
            [
                'user_id' => auth()->id(),
                'referrer' => $request->header('referer'),
                'device_type' => $this->detectDevice($request->userAgent()),
                'browser' => $this->detectBrowser($request->userAgent()),
                'os' => $this->detectOS($request->userAgent()),
                'ip_address' => $request->ip(),
            ]
        ));

        // Update session
        $this->updateSession($request->session_id);

        return response()->json(['message' => 'Event tracked', 'event_id' => $event->id]);
    }

    // Get overview statistics
    public function overview(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        $stats = [
            'total_events' => AnalyticEvent::byDateRange($startDate, $endDate)->count(),
            'total_sessions' => UserSession::byDateRange($startDate, $endDate)->count(),
            'total_users' => AnalyticEvent::byDateRange($startDate, $endDate)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'total_revenue' => AnalyticEvent::purchases()
                ->byDateRange($startDate, $endDate)
                ->sum('revenue'),
            'conversion_rate' => $this->calculateConversionRate($startDate, $endDate),
            'avg_session_duration' => UserSession::byDateRange($startDate, $endDate)
                ->avg('duration_seconds'),
            'bounce_rate' => $this->calculateBounceRate($startDate, $endDate),
        ];

        return response()->json($stats);
    }

    // Get real-time statistics
    public function realtime()
    {
        $lastMinute = now()->subMinute();

        $stats = [
            'active_users' => UserSession::where('ended_at', '>', $lastMinute)->count(),
            'page_views_last_minute' => AnalyticEvent::pageViews()
                ->where('created_at', '>', $lastMinute)
                ->count(),
            'recent_events' => AnalyticEvent::with('user:id,name')
                ->where('created_at', '>', now()->subMinutes(5))
                ->latest()
                ->limit(10)
                ->get(),
        ];

        return response()->json($stats);
    }

    // Get event timeline
    public function timeline(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());
        $eventType = $request->get('event_type');

        $query = AnalyticEvent::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        $timeline = $query->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($timeline);
    }

    // Get top pages
    public function topPages(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(7));
        $endDate = $request->get('end_date', now());
        $limit = $request->get('limit', 10);

        $pages = AnalyticEvent::pageViews()
            ->byDateRange($startDate, $endDate)
            ->selectRaw('page_url, COUNT(*) as views, COUNT(DISTINCT session_id) as unique_views')
            ->groupBy('page_url')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();

        return response()->json($pages);
    }

    // Get traffic sources
    public function trafficSources(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(7));
        $endDate = $request->get('end_date', now());

        $sources = UserSession::byDateRange($startDate, $endDate)
            ->selectRaw('utm_source, COUNT(*) as sessions, SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) as conversions')
            ->selectRaw('ROUND((SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as conversion_rate')
            ->groupBy('utm_source')
            ->orderByDesc('sessions')
            ->get();

        return response()->json($sources);
    }

    // Get device breakdown
    public function devices(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(7));
        $endDate = $request->get('end_date', now());

        $devices = UserSession::byDateRange($startDate, $endDate)
            ->selectRaw('device_type, COUNT(*) as sessions, SUM(revenue) as revenue')
            ->groupBy('device_type')
            ->get();

        return response()->json($devices);
    }

    // Get geographic data
    public function geography(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(7));
        $endDate = $request->get('end_date', now());

        $countries = AnalyticEvent::byDateRange($startDate, $endDate)
            ->selectRaw('country, COUNT(*) as events, COUNT(DISTINCT user_id) as users')
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('events')
            ->get();

        return response()->json($countries);
    }

    // Get user journey
    public function userJourney(Request $request, $userId)
    {
        $events = AnalyticEvent::where('user_id', $userId)
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        return response()->json($events);
    }

    // Helper methods
    protected function updateSession($sessionId)
    {
        $session = UserSession::where('session_id', $sessionId)->first();
        if ($session) {
            $session->incrementEvents();
        }
    }

    protected function calculateConversionRate($startDate, $endDate)
    {
        $totalSessions = UserSession::byDateRange($startDate, $endDate)->count();
        $convertedSessions = UserSession::byDateRange($startDate, $endDate)->converted()->count();

        return $totalSessions > 0 ? round(($convertedSessions / $totalSessions) * 100, 2) : 0;
    }

    protected function calculateBounceRate($startDate, $endDate)
    {
        $totalSessions = UserSession::byDateRange($startDate, $endDate)->count();
        $bouncedSessions = UserSession::byDateRange($startDate, $endDate)
            ->where('page_views', 1)
            ->count();

        return $totalSessions > 0 ? round(($bouncedSessions / $totalSessions) * 100, 2) : 0;
    }

    protected function detectDevice($userAgent)
    {
        if (preg_match('/mobile/i', $userAgent)) return 'Mobile';
        if (preg_match('/tablet/i', $userAgent)) return 'Tablet';
        return 'Desktop';
    }

    protected function detectBrowser($userAgent)
    {
        if (preg_match('/Chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/Safari/i', $userAgent)) return 'Safari';
        if (preg_match('/Firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/Edge/i', $userAgent)) return 'Edge';
        return 'Other';
    }

    protected function detectOS($userAgent)
    {
        if (preg_match('/Windows/i', $userAgent)) return 'Windows';
        if (preg_match('/Mac/i', $userAgent)) return 'macOS';
        if (preg_match('/Linux/i', $userAgent)) return 'Linux';
        if (preg_match('/Android/i', $userAgent)) return 'Android';
        if (preg_match('/iOS/i', $userAgent)) return 'iOS';
        return 'Other';
    }
}
