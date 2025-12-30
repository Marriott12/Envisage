<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    /**
     * Basic health check
     */
    public function index()
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'service' => 'Envisage AI Platform',
            'version' => config('app.version', '2.0.0')
        ]);
    }

    /**
     * Detailed health check with all services
     */
    public function detailed()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'ai_services' => $this->checkAIServices(),
        ];

        $overallHealth = collect($checks)->every(fn($check) => $check['healthy']);

        return response()->json([
            'status' => $overallHealth ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'system' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'uptime' => $this->getUptime(),
            ]
        ], $overallHealth ? 200 : 503);
    }

    /**
     * Check database connectivity
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $responseTime = $this->measureResponseTime(function() {
                DB::select('SELECT 1');
            });

            return [
                'healthy' => true,
                'response_time' => $responseTime . 'ms',
                'connection' => DB::connection()->getName()
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check cache system
     */
    protected function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            $value = 'test';
            
            Cache::put($key, $value, 60);
            $retrieved = Cache::get($key);
            Cache::forget($key);

            return [
                'healthy' => $retrieved === $value,
                'driver' => config('cache.default')
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    protected function checkRedis(): array
    {
        try {
            Redis::ping();
            
            $responseTime = $this->measureResponseTime(function() {
                Redis::set('health_check', 'test', 'EX', 60);
                Redis::get('health_check');
            });

            return [
                'healthy' => true,
                'response_time' => $responseTime . 'ms',
                'connection' => config('database.redis.default.host')
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check queue system
     */
    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $size = Queue::size($connection);

            return [
                'healthy' => true,
                'connection' => $connection,
                'pending_jobs' => $size,
                'status' => $size > 1000 ? 'warning' : 'normal'
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check storage accessibility
     */
    protected function checkStorage(): array
    {
        try {
            $paths = [
                'logs' => storage_path('logs'),
                'cache' => storage_path('framework/cache'),
                'sessions' => storage_path('framework/sessions'),
            ];

            $checks = [];
            foreach ($paths as $name => $path) {
                $checks[$name] = [
                    'writable' => is_writable($path),
                    'exists' => file_exists($path)
                ];
            }

            $healthy = collect($checks)->every(fn($check) => $check['writable'] && $check['exists']);

            return [
                'healthy' => $healthy,
                'paths' => $checks
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check AI services availability
     */
    protected function checkAIServices(): array
    {
        try {
            $services = [
                'recommendations' => class_exists(\App\Services\AdvancedRecommendationService::class),
                'fraud_detection' => class_exists(\App\Services\AdvancedFraudDetectionService::class),
                'sentiment_analysis' => class_exists(\App\Services\SentimentAnalysisService::class),
                'chatbot' => config('ai.chatbot.enabled', true),
                'visual_search' => config('ai.visual_search.enabled', true),
            ];

            return [
                'healthy' => collect($services)->every(fn($available) => $available),
                'services' => $services,
                'api_keys_configured' => [
                    'openai' => !empty(config('ai.openai.api_key')),
                    'google_vision' => !empty(config('ai.google_vision.api_key')),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get system metrics
     */
    public function metrics()
    {
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'metrics' => [
                'database' => [
                    'total_queries' => DB::getQueryLog() ? count(DB::getQueryLog()) : 0,
                    'active_connections' => $this->getActiveConnections(),
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'hit_rate' => $this->getCacheHitRate(),
                ],
                'queue' => [
                    'pending_jobs' => Queue::size(),
                    'failed_jobs' => DB::table('failed_jobs')->count(),
                ],
                'memory' => [
                    'usage' => memory_get_usage(true),
                    'peak' => memory_get_peak_usage(true),
                    'limit' => ini_get('memory_limit'),
                ],
                'requests' => [
                    'total' => Cache::get('metrics:total_requests', 0),
                    'errors' => Cache::get('metrics:total_errors', 0),
                ],
            ]
        ]);
    }

    /**
     * WebSocket health check
     */
    public function websocket()
    {
        try {
            $pusherConfigured = !empty(config('broadcasting.connections.pusher.key'));
            $broadcastingEnabled = config('broadcasting.default') !== 'log';

            return response()->json([
                'status' => $pusherConfigured && $broadcastingEnabled ? 'healthy' : 'degraded',
                'broadcasting' => [
                    'driver' => config('broadcasting.default'),
                    'pusher_configured' => $pusherConfigured,
                    'enabled' => $broadcastingEnabled,
                ],
                'channels' => [
                    'ai.recommendations' => 'active',
                    'ai.fraud' => 'active',
                    'ai.sentiment' => 'active',
                    'ai.chat' => 'active',
                    'ai.abtest' => 'active',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * Measure response time in milliseconds
     */
    protected function measureResponseTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Get system uptime
     */
    protected function getUptime(): string
    {
        if (function_exists('shell_exec')) {
            $uptime = shell_exec('uptime -p');
            return $uptime ?: 'N/A';
        }
        return 'N/A';
    }

    /**
     * Get active database connections
     */
    protected function getActiveConnections(): int
    {
        try {
            $result = DB::select("SHOW STATUS WHERE variable_name = 'Threads_connected'");
            return $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get cache hit rate
     */
    protected function getCacheHitRate(): float
    {
        $hits = Cache::get('metrics:cache_hits', 0);
        $misses = Cache::get('metrics:cache_misses', 0);
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
}
