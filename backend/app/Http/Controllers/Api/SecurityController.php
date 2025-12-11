<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityLog;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    /**
     * Get security logs for current user
     */
    public function logs(Request $request)
    {
        $user = $request->user();
        
        $logs = SecurityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($logs);
    }

    /**
     * Get recent login activity
     */
    public function loginActivity(Request $request)
    {
        $user = $request->user();
        
        $activity = SecurityLog::where('user_id', $user->id)
            ->whereIn('action', ['login', 'logout', 'failed_login'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'activity' => $activity,
            'last_login' => $user->last_login_at,
            'last_login_ip' => $user->last_login_ip
        ]);
    }

    /**
     * Log security event
     */
    public static function logEvent($userId, $action, $status = 'success', $reason = null, $metadata = [])
    {
        SecurityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => $status,
            'reason' => $reason,
            'metadata' => $metadata
        ]);
    }

    /**
     * Get active sessions
     */
    public function activeSessions(Request $request)
    {
        $user = $request->user();
        
        // Get recent login sessions
        $sessions = SecurityLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('ip_address')
            ->map(function ($logs) {
                $latest = $logs->first();
                return [
                    'ip_address' => $latest->ip_address,
                    'user_agent' => $latest->user_agent,
                    'last_active' => $latest->created_at,
                    'location' => $this->getLocationFromIP($latest->ip_address)
                ];
            })
            ->values();

        return response()->json([
            'sessions' => $sessions
        ]);
    }

    /**
     * Get suspicious activity alerts
     */
    public function suspiciousActivity(Request $request)
    {
        $user = $request->user();
        
        // Failed login attempts
        $failedLogins = SecurityLog::where('user_id', $user->id)
            ->where('action', 'failed_login')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // Login from new location/device
        $recentLogins = SecurityLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->where('created_at', '>=', now()->subDays(1))
            ->get();

        $newLocations = $recentLogins->filter(function ($log) use ($user) {
            return $log->ip_address !== $user->last_login_ip;
        });

        return response()->json([
            'failed_login_attempts' => $failedLogins,
            'new_locations' => $newLocations->count(),
            'alerts' => $this->generateAlerts($failedLogins, $newLocations)
        ]);
    }

    /**
     * Generate security alerts
     */
    protected function generateAlerts($failedLogins, $newLocations)
    {
        $alerts = [];

        if ($failedLogins > 5) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Multiple failed login attempts detected ({$failedLogins} attempts)",
                'action' => 'Consider changing your password'
            ];
        }

        if ($newLocations->count() > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "Login from new location detected",
                'action' => 'Review recent login activity'
            ];
        }

        return $alerts;
    }

    /**
     * Get approximate location from IP (simplified)
     */
    protected function getLocationFromIP($ip)
    {
        // In production, use a proper IP geolocation service
        return 'Unknown';
    }
}
