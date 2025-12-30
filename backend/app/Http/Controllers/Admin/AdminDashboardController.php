<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;

class AdminDashboardController extends Controller
{
    /**
     * Get comprehensive dashboard statistics
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'overview' => $this->getOverview(),
                'recent_activity' => $this->getRecentActivity(),
                'ai_metrics' => $this->getAIMetrics(),
                'system_health' => $this->getSystemHealth(),
            ],
        ]);
    }

    /**
     * Get real-time analytics
     */
    public function analytics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'users' => $this->getUserAnalytics(),
                'orders' => $this->getOrderAnalytics(),
                'products' => $this->getProductAnalytics(),
                'revenue' => $this->getRevenueAnalytics(),
            ],
        ]);
    }

    /**
     * Get AI system metrics
     */
    public function aiMetrics()
    {
        $cacheService = app(\App\Services\AdvancedCacheService::class);
        
        return response()->json([
            'success' => true,
            'data' => [
                'cache_stats' => $cacheService->getStats(),
                'recommendations' => $this->getRecommendationMetrics(),
                'fraud_detection' => $this->getFraudMetrics(),
                'sentiment_analysis' => $this->getSentimentMetrics(),
                'chatbot' => $this->getChatbotMetrics(),
            ],
        ]);
    }

    /**
     * Get queue monitoring data
     */
    public function queueMonitor()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'pending_jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
                'recent_failures' => DB::table('failed_jobs')
                    ->orderBy('failed_at', 'desc')
                    ->limit(10)
                    ->get(),
                'queue_stats' => $this->getQueueStats(),
            ],
        ]);
    }

    /**
     * Retry failed job
     */
    public function retryJob(Request $request)
    {
        $request->validate([
            'job_id' => 'required|integer',
        ]);

        $job = DB::table('failed_jobs')->find($request->job_id);
        
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found',
            ], 404);
        }

        // Re-queue the job
        DB::table('jobs')->insert([
            'queue' => $job->queue,
            'payload' => $job->payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        // Delete from failed jobs
        DB::table('failed_jobs')->where('id', $request->job_id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job requeued successfully',
        ]);
    }

    /**
     * Get user management data
     */
    public function users(Request $request)
    {
        $query = User::query()->with('roles');

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Update user status
     */
    public function updateUserStatus(Request $request, $userId)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $user = User::findOrFail($userId);
        $user->status = $request->status;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * Get system configuration
     */
    public function configuration()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'ai' => [
                    'recommendation_enabled' => config('ai.recommendation.enabled'),
                    'fraud_detection_enabled' => config('ai.fraud_detection.enabled'),
                    'sentiment_analysis_enabled' => config('ai.sentiment.enabled'),
                    'chatbot_enabled' => config('ai.chatbot.enabled'),
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'ttl' => config('cache.ttl'),
                ],
                'queue' => [
                    'driver' => config('queue.default'),
                    'retry_after' => config('queue.connections.redis.retry_after'),
                ],
                'broadcasting' => [
                    'driver' => config('broadcasting.default'),
                    'pusher_configured' => !empty(config('broadcasting.connections.pusher.key')),
                ],
            ],
        ]);
    }

    // Protected helper methods

    protected function getOverview(): array
    {
        return [
            'total_users' => User::count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'active_sessions' => $this->getActiveSessions(),
        ];
    }

    protected function getRecentActivity(): array
    {
        return [
            'recent_orders' => Order::with('user', 'items')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'new_users' => User::orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'new_products' => Product::orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    protected function getAIMetrics(): array
    {
        return [
            'total_recommendations' => Cache::get('metrics:recommendations:total', 0),
            'fraud_analyses' => Cache::get('metrics:fraud:total', 0),
            'sentiment_analyses' => Cache::get('metrics:sentiment:total', 0),
            'chatbot_conversations' => Cache::get('metrics:chatbot:total', 0),
        ];
    }

    protected function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];
    }

    protected function getUserAnalytics(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'new_today' => User::whereDate('created_at', today())->count(),
            'new_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            'by_role' => User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('roles.name', DB::raw('count(*) as count'))
                ->groupBy('roles.name')
                ->get(),
        ];
    }

    protected function getOrderAnalytics(): array
    {
        return [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'today' => Order::whereDate('created_at', today())->count(),
        ];
    }

    protected function getProductAnalytics(): array
    {
        return [
            'total' => Product::count(),
            'active' => Product::where('status', 'active')->count(),
            'out_of_stock' => Product::where('stock', '<=', 0)->count(),
            'low_stock' => Product::whereBetween('stock', [1, 10])->count(),
        ];
    }

    protected function getRevenueAnalytics(): array
    {
        return [
            'today' => Order::whereDate('created_at', today())->sum('total_amount'),
            'this_week' => Order::whereBetween('created_at', [now()->startOfWeek(), now()])->sum('total_amount'),
            'this_month' => Order::whereMonth('created_at', now()->month)->sum('total_amount'),
            'this_year' => Order::whereYear('created_at', now()->year)->sum('total_amount'),
        ];
    }

    protected function getRecommendationMetrics(): array
    {
        return [
            'total_generated' => Cache::get('metrics:recommendations:generated', 0),
            'algorithms_used' => [
                'neural' => Cache::get('metrics:recommendations:neural', 0),
                'bandit' => Cache::get('metrics:recommendations:bandit', 0),
                'session' => Cache::get('metrics:recommendations:session', 0),
            ],
        ];
    }

    protected function getFraudMetrics(): array
    {
        return [
            'total_analyses' => Cache::get('metrics:fraud:analyses', 0),
            'high_risk_detected' => Cache::get('metrics:fraud:high_risk', 0),
            'alerts_created' => Cache::get('metrics:fraud:alerts', 0),
        ];
    }

    protected function getSentimentMetrics(): array
    {
        return [
            'total_analyses' => Cache::get('metrics:sentiment:analyses', 0),
            'positive' => Cache::get('metrics:sentiment:positive', 0),
            'negative' => Cache::get('metrics:sentiment:negative', 0),
            'neutral' => Cache::get('metrics:sentiment:neutral', 0),
        ];
    }

    protected function getChatbotMetrics(): array
    {
        return [
            'total_conversations' => Cache::get('metrics:chatbot:conversations', 0),
            'total_messages' => Cache::get('metrics:chatbot:messages', 0),
            'active_conversations' => Cache::get('metrics:chatbot:active', 0),
        ];
    }

    protected function getQueueStats(): array
    {
        return [
            'default' => DB::table('jobs')->where('queue', 'default')->count(),
            'high' => DB::table('jobs')->where('queue', 'high')->count(),
            'low' => DB::table('jobs')->where('queue', 'low')->count(),
        ];
    }

    protected function getActiveSessions(): int
    {
        // This is a simplified count - actual implementation depends on session driver
        return Cache::get('metrics:active_sessions', 0);
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkCache(): bool
    {
        try {
            Cache::put('health_check', 'test', 60);
            return Cache::get('health_check') === 'test';
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkQueue(): bool
    {
        try {
            return DB::table('jobs')->count() >= 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
