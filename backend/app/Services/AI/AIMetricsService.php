<?php

namespace App\Services\AI;

use App\Models\AIMetric;
use App\Models\AICost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AIMetricsService
{
    /**
     * Track AI service request
     *
     * @param string $service Service name (recommendations, chatbot, etc.)
     * @param string $endpoint API endpoint
     * @param int $responseTimeMs Response time in milliseconds
     * @param bool $success Whether request succeeded
     * @param array $metadata Additional metrics
     * @return void
     */
    public function trackRequest(
        string $service,
        string $endpoint,
        int $responseTimeMs,
        bool $success = true,
        array $metadata = []
    ): void {
        if (!config('ai.monitoring.enabled')) {
            return;
        }
        
        try {
            AIMetric::create([
                'service' => $service,
                'user_id' => Auth::id(),
                'endpoint' => $endpoint,
                'response_time_ms' => $responseTimeMs,
                'success' => $success,
                'error_message' => $metadata['error'] ?? null,
                'tokens_used' => $metadata['tokens'] ?? null,
                'cost_usd' => $metadata['cost'] ?? null,
                'metadata' => json_encode($metadata),
            ]);
            
            // Log slow queries
            if ($responseTimeMs > config('ai.monitoring.slow_query_threshold', 1000)) {
                Log::warning("Slow AI Query: {$service}/{$endpoint}", [
                    'response_time_ms' => $responseTimeMs,
                    'metadata' => $metadata,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to track AI metrics: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate and update daily cost summary
     *
     * @param string $service
     * @param \DateTime|null $date
     * @return void
     */
    public function updateDailyCosts(string $service, ?\DateTime $date = null): void
    {
        $date = $date ?? now();
        $dateString = $date->format('Y-m-d');
        
        $stats = AIMetric::where('service', $service)
            ->whereDate('created_at', $dateString)
            ->select([
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_requests'),
                DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_requests'),
                DB::raw('SUM(tokens_used) as total_tokens'),
                DB::raw('SUM(cost_usd) as total_cost_usd'),
                DB::raw('AVG(response_time_ms) as avg_response_time_ms'),
            ])
            ->first();
        
        if ($stats->total_requests > 0) {
            AICost::updateOrCreate(
                [
                    'service' => $service,
                    'date' => $dateString,
                ],
                [
                    'total_requests' => $stats->total_requests,
                    'successful_requests' => $stats->successful_requests,
                    'failed_requests' => $stats->failed_requests,
                    'total_tokens' => $stats->total_tokens ?? 0,
                    'total_cost_usd' => $stats->total_cost_usd ?? 0,
                    'avg_response_time_ms' => round($stats->avg_response_time_ms, 2),
                ]
            );
        }
    }
    
    /**
     * Get service performance metrics
     *
     * @param string $service
     * @param int $days Number of days to analyze
     * @return array
     */
    public function getServiceMetrics(string $service, int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        $metrics = AIMetric::where('service', $service)
            ->where('created_at', '>=', $startDate)
            ->select([
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_requests'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('MAX(response_time_ms) as max_response_time'),
                DB::raw('MIN(response_time_ms) as min_response_time'),
                DB::raw('SUM(tokens_used) as total_tokens'),
                DB::raw('SUM(cost_usd) as total_cost'),
            ])
            ->first();
        
        $successRate = $metrics->total_requests > 0
            ? ($metrics->successful_requests / $metrics->total_requests) * 100
            : 0;
        
        return [
            'service' => $service,
            'period_days' => $days,
            'total_requests' => $metrics->total_requests,
            'successful_requests' => $metrics->successful_requests,
            'failed_requests' => $metrics->total_requests - $metrics->successful_requests,
            'success_rate_percent' => round($successRate, 2),
            'avg_response_time_ms' => round($metrics->avg_response_time ?? 0, 2),
            'max_response_time_ms' => $metrics->max_response_time ?? 0,
            'min_response_time_ms' => $metrics->min_response_time ?? 0,
            'total_tokens_used' => $metrics->total_tokens ?? 0,
            'total_cost_usd' => round($metrics->total_cost ?? 0, 2),
            'avg_cost_per_request' => $metrics->total_requests > 0
                ? round(($metrics->total_cost ?? 0) / $metrics->total_requests, 4)
                : 0,
        ];
    }
    
    /**
     * Get cost summary for all services
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return array
     */
    public function getCostSummary(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now();
        
        $costs = AICost::whereBetween('date', [$startDate, $endDate])
            ->select('service', DB::raw('SUM(total_cost_usd) as total_cost'))
            ->groupBy('service')
            ->get()
            ->keyBy('service');
        
        $totalCost = $costs->sum('total_cost');
        $dailyBudget = config('ai.costs.budget_alerts.daily_limit', 100);
        $monthlyBudget = config('ai.costs.budget_alerts.monthly_limit', 2000);
        
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;
        $projectedMonthlyCost = ($totalCost / $daysInPeriod) * 30;
        
        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $daysInPeriod,
            ],
            'total_cost_usd' => round($totalCost, 2),
            'avg_daily_cost_usd' => round($totalCost / max($daysInPeriod, 1), 2),
            'projected_monthly_cost_usd' => round($projectedMonthlyCost, 2),
            'budgets' => [
                'daily_limit' => $dailyBudget,
                'monthly_limit' => $monthlyBudget,
                'monthly_usage_percent' => round(($projectedMonthlyCost / $monthlyBudget) * 100, 2),
                'budget_alert' => $projectedMonthlyCost > ($monthlyBudget * 0.8),
            ],
            'by_service' => $costs->map(function($cost) use ($totalCost) {
                return [
                    'cost_usd' => round($cost->total_cost, 2),
                    'percent_of_total' => round(($cost->total_cost / $totalCost) * 100, 2),
                ];
            })->toArray(),
        ];
    }
    
    /**
     * Check if budget alerts should be sent
     *
     * @return array
     */
    public function checkBudgetAlerts(): array
    {
        $today = AICost::whereDate('date', now()->format('Y-m-d'))
            ->sum('total_cost_usd');
        
        $thisMonth = AICost::whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('total_cost_usd');
        
        $dailyLimit = config('ai.costs.budget_alerts.daily_limit', 100);
        $monthlyLimit = config('ai.costs.budget_alerts.monthly_limit', 2000);
        $threshold = config('ai.costs.budget_alerts.alert_threshold', 0.8);
        
        $alerts = [];
        
        if ($today >= $dailyLimit * $threshold) {
            $alerts[] = [
                'type' => 'daily_budget',
                'severity' => $today >= $dailyLimit ? 'critical' : 'warning',
                'message' => "Daily AI costs: $" . round($today, 2) . " / $" . $dailyLimit,
                'usage_percent' => round(($today / $dailyLimit) * 100, 2),
            ];
        }
        
        if ($thisMonth >= $monthlyLimit * $threshold) {
            $alerts[] = [
                'type' => 'monthly_budget',
                'severity' => $thisMonth >= $monthlyLimit ? 'critical' : 'warning',
                'message' => "Monthly AI costs: $" . round($thisMonth, 2) . " / $" . $monthlyLimit,
                'usage_percent' => round(($thisMonth / $monthlyLimit) * 100, 2),
            ];
        }
        
        return $alerts;
    }
}
