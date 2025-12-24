<?php

namespace App\Jobs\AI;

use App\Models\User;
use App\Models\Product;
use App\Models\Recommendation;
use App\Services\AICacheService;
use App\Services\AIMetricsService;
use App\Events\AI\RecommendationGenerated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateRecommendationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $context;
    public $algorithm;
    public $count;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 60;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId, $context = [], $algorithm = 'neural', $count = 10)
    {
        $this->userId = $userId;
        $this->context = $context;
        $this->algorithm = $algorithm;
        $this->count = $count;
    }

    /**
     * Execute the job - Generate personalized recommendations
     *
     * @return void
     */
    public function handle(AICacheService $cacheService, AIMetricsService $metricsService)
    {
        $startTime = microtime(true);

        try {
            $user = $this->userId ? User::find($this->userId) : null;

            // Generate cache key
            $cacheKey = "user_{$this->userId}_algo_{$this->algorithm}_" . md5(json_encode($this->context));

            // Check cache first
            $recommendations = $cacheService->remember(
                'recommendations',
                $cacheKey,
                function () use ($user) {
                    return $this->generateRecommendations($user);
                }
            );

            // Track metrics
            $responseTime = (microtime(true) - $startTime) * 1000;
            $metricsService->trackRequest(
                'recommendations',
                'generate',
                $responseTime,
                true,
                [
                    'algorithm' => $this->algorithm,
                    'user_id' => $this->userId,
                    'count' => count($recommendations),
                    'cached' => false,
                ]
            );

            Log::info('Recommendations generated successfully', [
                'user_id' => $this->userId,
                'algorithm' => $this->algorithm,
                'count' => count($recommendations),
                'time_ms' => $responseTime,
            ]);

            // Broadcast real-time event
            event(new RecommendationGenerated(
                $this->userId,
                $recommendations,
                $this->algorithm,
                $responseTime
            ));

        } catch (\Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $metricsService->trackRequest(
                'recommendations',
                'generate',
                $responseTime,
                false,
                [
                    'error' => $e->getMessage(),
                    'user_id' => $this->userId,
                ]
            );

            Log::error('Recommendation generation failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate recommendations based on algorithm
     *
     * @param User|null $user
     * @return array
     */
    protected function generateRecommendations($user)
    {
        // Algorithm weights from config
        $algorithms = config('ai.recommendations.algorithms', []);
        
        switch ($this->algorithm) {
            case 'neural':
                return $this->neuralRecommendations($user);
            case 'bandit':
                return $this->banditRecommendations($user);
            case 'session':
                return $this->sessionBasedRecommendations($user);
            case 'context':
                return $this->contextAwareRecommendations($user);
            default:
                return $this->hybridRecommendations($user, $algorithms);
        }
    }

    protected function neuralRecommendations($user)
    {
        // Placeholder for neural network recommendations
        // In production, this would call TensorFlow/PyTorch model
        return Product::inRandomOrder()->limit($this->count)->get()->toArray();
    }

    protected function banditRecommendations($user)
    {
        // Multi-armed bandit algorithm - balance exploration/exploitation
        return Product::where('stock', '>', 0)
            ->inRandomOrder()
            ->limit($this->count)
            ->get()
            ->toArray();
    }

    protected function sessionBasedRecommendations($user)
    {
        // Session-based recommendations using recent activity
        $recentlyViewed = $this->context['recently_viewed'] ?? [];
        
        if (empty($recentlyViewed)) {
            return $this->neuralRecommendations($user);
        }

        // Find similar products
        $categoryIds = Product::whereIn('id', $recentlyViewed)
            ->pluck('category_id')
            ->unique();

        return Product::whereIn('category_id', $categoryIds)
            ->whereNotIn('id', $recentlyViewed)
            ->where('stock', '>', 0)
            ->inRandomOrder()
            ->limit($this->count)
            ->get()
            ->toArray();
    }

    protected function contextAwareRecommendations($user)
    {
        // Context-aware recommendations based on page, time, etc.
        $pageType = $this->context['page_type'] ?? 'home';
        
        $query = Product::where('stock', '>', 0);

        if (isset($this->context['category_id'])) {
            $query->where('category_id', $this->context['category_id']);
        }

        if (isset($this->context['price_range'])) {
            $query->whereBetween('price', [
                $this->context['price_range']['min'] ?? 0,
                $this->context['price_range']['max'] ?? 999999,
            ]);
        }

        return $query->inRandomOrder()->limit($this->count)->get()->toArray();
    }

    protected function hybridRecommendations($user, $algorithms)
    {
        // Combine multiple algorithms based on weights
        $results = [];
        
        foreach ($algorithms as $algo => $weight) {
            $count = (int) ($this->count * $weight);
            $this->algorithm = $algo;
            $algoResults = $this->generateRecommendations($user);
            $results = array_merge($results, array_slice($algoResults, 0, $count));
        }

        return array_slice($results, 0, $this->count);
    }
}
