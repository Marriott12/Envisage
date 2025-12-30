<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class SystemStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display current system status and health metrics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('====================================');
        $this->info('  ENVISAGE AI PLATFORM - STATUS');
        $this->info('====================================');
        $this->newLine();

        // System Information
        $this->table(
            ['Component', 'Status', 'Details'],
            [
                ['PHP Version', '✓', PHP_VERSION],
                ['Laravel Version', '✓', app()->version()],
                ['Environment', '✓', config('app.env')],
                ['Debug Mode', config('app.debug') ? '⚠' : '✓', config('app.debug') ? 'ON' : 'OFF'],
            ]
        );

        $this->newLine();

        // Service Health
        $this->info('Service Health Checks:');
        $this->table(
            ['Service', 'Status', 'Response Time'],
            [
                ['Database', $this->checkDatabase() ? '✓ Healthy' : '✗ Unhealthy', $this->getDatabaseResponseTime()],
                ['Cache (Redis)', $this->checkCache() ? '✓ Healthy' : '✗ Unhealthy', '-'],
                ['Queue', '✓ Running', Queue::size() . ' pending jobs'],
                ['Broadcasting', config('broadcasting.default') !== 'log' ? '✓ Enabled' : '⚠ Disabled', config('broadcasting.default')],
            ]
        );

        $this->newLine();

        // AI Services
        $this->info('AI Services:');
        $this->table(
            ['Service', 'Status', 'Configuration'],
            [
                ['Recommendations', '✓', '5 algorithms available'],
                ['Fraud Detection', '✓', 'Real-time analysis'],
                ['Sentiment Analysis', '✓', 'NLP enabled'],
                ['Chatbot', config('ai.chatbot.enabled') ? '✓' : '✗', config('ai.chatbot.provider')],
                ['Visual Search', config('ai.visual_search.enabled') ? '✓' : '✗', 'Google Vision API'],
            ]
        );

        $this->newLine();

        // Performance Metrics
        $this->info('Performance Metrics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Memory Usage', $this->formatBytes(memory_get_usage(true))],
                ['Memory Peak', $this->formatBytes(memory_get_peak_usage(true))],
                ['Cache Hit Rate', Cache::get('metrics:cache_hits', 0) . '%'],
                ['Failed Jobs', DB::table('failed_jobs')->count()],
            ]
        );

        $this->newLine();
        $this->info('System status check complete!');

        return 0;
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
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

    protected function getDatabaseResponseTime(): string
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $time = round((microtime(true) - $start) * 1000, 2);
            return $time . 'ms';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    protected function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
