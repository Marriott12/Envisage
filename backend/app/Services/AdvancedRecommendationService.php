<?php

namespace App\Services;

use App\Models\Product;
use App\Models\UserProductInteraction;
use App\Models\PersonalizedRecommendation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Advanced AI Recommendation Engine
 * 
 * Features:
 * - Neural Collaborative Filtering (NCF)
 * - Multi-Armed Bandits for exploration-exploitation
 * - Session-based recommendations with RNN
 * - Context-aware recommendations
 * - Real-time learning and adaptation
 * - A/B testing support
 */
class AdvancedRecommendationService
{
    protected $mlServiceUrl;
    protected $exploreRate = 0.15; // 15% exploration for multi-armed bandits
    
    public function __construct()
    {
        $this->mlServiceUrl = config('services.ml.url', env('ML_SERVICE_URL', 'http://localhost:5000'));
    }

    /**
     * Get recommendations using Neural Collaborative Filtering
     * Combines matrix factorization with deep neural networks
     */
    public function getNeuralRecommendations($userId, $limit = 20, $context = [])
    {
        $cacheKey = "neural_rec:{$userId}:" . md5(json_encode($context));
        
        return Cache::remember($cacheKey, 3600, function () use ($userId, $limit, $context) {
            try {
                // Call Python ML service for neural predictions
                $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/recommend/neural", [
                    'user_id' => $userId,
                    'limit' => $limit,
                    'context' => $context,
                    'model_type' => 'ncf', // Neural Collaborative Filtering
                ]);

                if ($response->successful()) {
                    $predictions = $response->json()['recommendations'];
                    return $this->formatRecommendations($predictions);
                }
            } catch (\Exception $e) {
                \Log::warning("Neural recommendation service unavailable: " . $e->getMessage());
            }

            // Fallback to traditional collaborative filtering
            return $this->fallbackRecommendations($userId, $limit);
        });
    }

    /**
     * Multi-Armed Bandit Algorithm for exploration-exploitation
     * Balances showing proven popular items vs exploring new items
     */
    public function getBanditRecommendations($userId, $limit = 20, $slotContext = [])
    {
        $recommendations = [];
        
        // Calculate number of exploration vs exploitation slots
        $exploreCount = (int)($limit * $this->exploreRate);
        $exploitCount = $limit - $exploreCount;

        // EXPLOITATION: Get top predicted items (greedy)
        $exploitItems = $this->getNeuralRecommendations($userId, $exploitCount * 2);
        
        // Apply Thompson Sampling for selection
        $selectedExploit = $this->thompsonSampling($exploitItems, $exploitCount, $userId);

        // EXPLORATION: Get diverse items (epsilon-greedy)
        $exploreItems = $this->getExplorationCandidates($userId, $exploreCount * 3);
        $selectedExplore = $this->selectDiverseItems($exploreItems, $exploreCount);

        // Merge and shuffle
        $recommendations = array_merge($selectedExploit, $selectedExplore);
        shuffle($recommendations);

        // Track impressions for learning
        $this->trackImpressions($recommendations, $userId, 'bandit');

        return collect($recommendations)->take($limit);
    }

    /**
     * Thompson Sampling: Bayesian approach to multi-armed bandits
     * Maintains beta distribution for each item's success rate
     */
    protected function thompsonSampling($candidates, $count, $userId)
    {
        $samples = [];

        foreach ($candidates as $product) {
            // Get historical performance
            $stats = $this->getProductPerformanceStats($product->id, $userId);
            
            // Beta distribution parameters (successes + 1, failures + 1)
            $alpha = ($stats['clicks'] ?? 0) + 1;
            $beta = ($stats['impressions'] ?? 1) - ($stats['clicks'] ?? 0) + 1;
            
            // Sample from beta distribution
            $sample = $this->betaSample($alpha, $beta);
            
            $samples[$product->id] = [
                'product' => $product,
                'score' => $sample,
            ];
        }

        // Sort by sampled score and select top N
        usort($samples, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return array_map(fn($s) => $s['product'], array_slice($samples, 0, $count));
    }

    /**
     * Beta distribution sampling (simplified)
     */
    protected function betaSample($alpha, $beta)
    {
        // Using ratio of gammas approximation
        $x = $this->gammaSample($alpha);
        $y = $this->gammaSample($beta);
        
        return $x / ($x + $y);
    }

    /**
     * Gamma distribution sampling (Marsaglia-Tsang method)
     */
    protected function gammaSample($shape, $scale = 1.0)
    {
        if ($shape < 1) {
            return $this->gammaSample($shape + 1, $scale) * pow(mt_rand() / mt_getrandmax(), 1.0 / $shape);
        }

        $d = $shape - 1.0 / 3.0;
        $c = 1.0 / sqrt(9.0 * $d);

        while (true) {
            $x = $this->normalSample();
            $v = 1.0 + $c * $x;
            
            if ($v <= 0) continue;
            
            $v = $v * $v * $v;
            $u = mt_rand() / mt_getrandmax();
            
            if ($u < 1 - 0.0331 * $x * $x * $x * $x) {
                return $d * $v * $scale;
            }
            
            if (log($u) < 0.5 * $x * $x + $d * (1 - $v + log($v))) {
                return $d * $v * $scale;
            }
        }
    }

    /**
     * Normal distribution sampling (Box-Muller transform)
     */
    protected function normalSample($mean = 0, $stddev = 1)
    {
        static $cached = null;
        
        if ($cached !== null) {
            $result = $cached;
            $cached = null;
            return $mean + $stddev * $result;
        }

        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();
        
        $r = sqrt(-2 * log($u1));
        $theta = 2 * M_PI * $u2;
        
        $cached = $r * sin($theta);
        return $mean + $stddev * $r * cos($theta);
    }

    /**
     * Session-based recommendations using RNN/GRU
     * Predicts next item based on current session sequence
     */
    public function getSessionBasedRecommendations($sessionId, $viewedProducts, $limit = 10)
    {
        $cacheKey = "session_rec:{$sessionId}:" . md5(implode(',', $viewedProducts));
        
        return Cache::remember($cacheKey, 1800, function () use ($sessionId, $viewedProducts, $limit) {
            try {
                // Call Python ML service with GRU model
                $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/recommend/session", [
                    'session_id' => $sessionId,
                    'sequence' => $viewedProducts,
                    'limit' => $limit,
                    'model_type' => 'gru4rec',
                ]);

                if ($response->successful()) {
                    $predictions = $response->json()['recommendations'];
                    return $this->formatRecommendations($predictions);
                }
            } catch (\Exception $e) {
                \Log::warning("Session-based recommendation failed: " . $e->getMessage());
            }

            // Fallback: use collaborative filtering on last items
            return $this->coViewedProducts(array_slice($viewedProducts, -3), $limit);
        });
    }

    /**
     * Context-aware recommendations
     * Considers time, location, device, weather, events
     */
    public function getContextAwareRecommendations($userId, $limit = 20)
    {
        $context = $this->buildContext();
        
        $cacheKey = "context_rec:{$userId}:" . md5(json_encode($context));
        
        return Cache::remember($cacheKey, 1800, function () use ($userId, $limit, $context) {
            // Get base recommendations
            $baseRecs = $this->getNeuralRecommendations($userId, $limit * 2, $context);
            
            // Apply contextual boosting
            $scoredRecs = [];
            foreach ($baseRecs as $product) {
                $score = 1.0;
                
                // Time-based boosting
                $score *= $this->getTimeBoost($product, $context['hour'], $context['day_of_week']);
                
                // Seasonal boosting
                $score *= $this->getSeasonalBoost($product, $context['month']);
                
                // Weather boosting (if available)
                if (isset($context['weather'])) {
                    $score *= $this->getWeatherBoost($product, $context['weather']);
                }
                
                // Device-specific boosting
                $score *= $this->getDeviceBoost($product, $context['device_type']);
                
                $scoredRecs[$product->id] = [
                    'product' => $product,
                    'score' => $score,
                ];
            }
            
            // Sort by contextual score
            usort($scoredRecs, fn($a, $b) => $b['score'] <=> $a['score']);
            
            return collect(array_map(fn($s) => $s['product'], array_slice($scoredRecs, 0, $limit)));
        });
    }

    /**
     * Build context information
     */
    protected function buildContext()
    {
        return [
            'hour' => (int)date('H'),
            'day_of_week' => (int)date('w'),
            'month' => (int)date('n'),
            'is_weekend' => in_array(date('w'), [0, 6]),
            'device_type' => $this->detectDeviceType(),
            'weather' => $this->getWeatherCondition(),
        ];
    }

    /**
     * Time-based boosting
     */
    protected function getTimeBoost($product, $hour, $dayOfWeek)
    {
        // Get historical conversion rates by time
        $stats = Cache::remember("time_stats:{$product->id}", 86400, function () use ($product) {
            return UserProductInteraction::where('product_id', $product->id)
                ->where('interaction_type', 'purchase')
                ->selectRaw('HOUR(interacted_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->pluck('count', 'hour')
                ->toArray();
        });

        if (empty($stats)) return 1.0;

        $avgCount = array_sum($stats) / count($stats);
        $hourCount = $stats[$hour] ?? 0;

        // Boost if this hour performs better than average
        return 1.0 + (($hourCount - $avgCount) / max($avgCount, 1)) * 0.2;
    }

    /**
     * Seasonal boosting
     */
    protected function getSeasonalBoost($product, $month)
    {
        // Define seasonal categories
        $seasonalCategories = [
            'winter_clothing' => [11, 12, 1, 2],
            'summer_clothing' => [5, 6, 7, 8],
            'back_to_school' => [8, 9],
            'holiday_gifts' => [11, 12],
            'spring_garden' => [3, 4, 5],
        ];

        $boost = 1.0;
        
        // Check if product is in seasonal category
        foreach ($seasonalCategories as $category => $months) {
            if (in_array($month, $months)) {
                if (stripos($product->name, str_replace('_', ' ', $category)) !== false ||
                    stripos($product->category->name ?? '', str_replace('_', ' ', $category)) !== false) {
                    $boost = 1.5; // 50% boost for seasonal items
                }
            }
        }

        return $boost;
    }

    /**
     * Weather-based boosting
     */
    protected function getWeatherBoost($product, $weather)
    {
        $weatherKeywords = [
            'rainy' => ['umbrella', 'raincoat', 'boots', 'waterproof'],
            'sunny' => ['sunglasses', 'sunscreen', 'hat', 'beach'],
            'cold' => ['jacket', 'coat', 'sweater', 'heater'],
            'hot' => ['fan', 'ac', 'shorts', 'tank'],
        ];

        $condition = $weather['condition'] ?? 'normal';
        
        if (!isset($weatherKeywords[$condition])) return 1.0;

        foreach ($weatherKeywords[$condition] as $keyword) {
            if (stripos($product->name, $keyword) !== false ||
                stripos($product->description, $keyword) !== false) {
                return 1.3; // 30% boost
            }
        }

        return 1.0;
    }

    /**
     * Device-specific boosting
     */
    protected function getDeviceBoost($product, $deviceType)
    {
        // Boost mobile-friendly products on mobile
        if ($deviceType === 'mobile') {
            $mobileFriendly = ['digital', 'ebook', 'music', 'app', 'game'];
            
            foreach ($mobileFriendly as $keyword) {
                if (stripos($product->name, $keyword) !== false) {
                    return 1.2;
                }
            }
        }

        return 1.0;
    }

    /**
     * Real-time learning: Update model with user feedback
     */
    public function updateWithFeedback($userId, $productId, $action, $context = [])
    {
        // Store interaction
        UserProductInteraction::trackInteraction($userId, $productId, $action);

        // Send to ML service for online learning
        try {
            Http::async()->post("{$this->mlServiceUrl}/api/learn/online", [
                'user_id' => $userId,
                'product_id' => $productId,
                'action' => $action,
                'context' => $context,
                'timestamp' => now()->timestamp,
            ]);
        } catch (\Exception $e) {
            \Log::warning("Online learning update failed: " . $e->getMessage());
        }

        // Clear relevant caches
        Cache::tags(['recommendations', "user:{$userId}"])->flush();
    }

    /**
     * A/B Testing support: Assign users to experiment variants
     */
    public function getExperimentVariant($userId, $experimentName)
    {
        $hash = crc32("{$experimentName}:{$userId}");
        $bucket = $hash % 100;

        // Define experiment configurations
        $experiments = config('ai.experiments', [
            'recommendation_algorithm' => [
                'enabled' => true,
                'variants' => [
                    'control' => ['weight' => 50, 'algorithm' => 'collaborative'],
                    'neural' => ['weight' => 30, 'algorithm' => 'ncf'],
                    'bandit' => ['weight' => 20, 'algorithm' => 'thompson_sampling'],
                ],
            ],
        ]);

        $experiment = $experiments[$experimentName] ?? null;
        
        if (!$experiment || !$experiment['enabled']) {
            return 'control';
        }

        $cumulative = 0;
        foreach ($experiment['variants'] as $variant => $config) {
            $cumulative += $config['weight'];
            if ($bucket < $cumulative) {
                return $variant;
            }
        }

        return 'control';
    }

    /**
     * Get recommendations based on A/B test variant
     */
    public function getRecommendationsWithExperiment($userId, $limit = 20)
    {
        $variant = $this->getExperimentVariant($userId, 'recommendation_algorithm');

        switch ($variant) {
            case 'neural':
                $recs = $this->getNeuralRecommendations($userId, $limit);
                break;
            case 'bandit':
                $recs = $this->getBanditRecommendations($userId, $limit);
                break;
            default:
                $recs = $this->fallbackRecommendations($userId, $limit);
        }

        // Track experiment exposure
        $this->trackExperimentExposure($userId, 'recommendation_algorithm', $variant);

        return $recs;
    }

    /**
     * Diversity-aware ranking
     * Ensures recommendations are diverse across categories, prices, brands
     */
    public function diversifyRecommendations($recommendations, $diversityWeight = 0.3)
    {
        $diversified = [];
        $seenCategories = [];
        $seenBrands = [];
        $priceRanges = [];

        foreach ($recommendations as $product) {
            $diversityScore = 1.0;

            // Category diversity
            $categoryId = $product->category_id;
            $categoryCount = $seenCategories[$categoryId] ?? 0;
            $diversityScore *= 1.0 / (1.0 + $categoryCount * 0.3);
            $seenCategories[$categoryId] = $categoryCount + 1;

            // Brand diversity
            $brand = $product->brand ?? 'unknown';
            $brandCount = $seenBrands[$brand] ?? 0;
            $diversityScore *= 1.0 / (1.0 + $brandCount * 0.2);
            $seenBrands[$brand] = $brandCount + 1;

            // Price diversity
            $priceRange = $this->getPriceRange($product->price);
            $priceCount = $priceRanges[$priceRange] ?? 0;
            $diversityScore *= 1.0 / (1.0 + $priceCount * 0.15);
            $priceRanges[$priceRange] = $priceCount + 1;

            $diversified[] = [
                'product' => $product,
                'score' => $product->recommendation_score ?? 1.0,
                'diversity' => $diversityScore,
            ];
        }

        // Re-rank with diversity
        foreach ($diversified as &$item) {
            $item['final_score'] = 
                $item['score'] * (1 - $diversityWeight) + 
                $item['diversity'] * $diversityWeight;
        }

        usort($diversified, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

        return collect(array_map(fn($d) => $d['product'], $diversified));
    }

    /**
     * Get price range bucket
     */
    protected function getPriceRange($price)
    {
        if ($price < 20) return 'budget';
        if ($price < 50) return 'low';
        if ($price < 100) return 'mid';
        if ($price < 500) return 'high';
        return 'premium';
    }

    /**
     * Detect device type from request
     */
    protected function detectDeviceType()
    {
        $userAgent = request()->header('User-Agent', '');
        
        if (preg_match('/mobile|android|iphone/i', $userAgent)) {
            return 'mobile';
        }
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }
        return 'desktop';
    }

    /**
     * Get weather condition (mock - integrate with weather API)
     */
    protected function getWeatherCondition()
    {
        // TODO: Integrate with OpenWeather or similar API
        return ['condition' => 'normal', 'temp' => 20];
    }

    /**
     * Get exploration candidates
     */
    protected function getExplorationCandidates($userId, $count)
    {
        // Get recently added products, trending items, or random samples
        return Product::where('created_at', '>=', now()->subDays(30))
            ->whereNotIn('id', function ($query) use ($userId) {
                $query->select('product_id')
                    ->from('user_product_interactions')
                    ->where('user_id', $userId);
            })
            ->inRandomOrder()
            ->limit($count)
            ->get();
    }

    /**
     * Select diverse items from candidates
     */
    protected function selectDiverseItems($candidates, $count)
    {
        return $this->diversifyRecommendations($candidates, 0.5)->take($count)->all();
    }

    /**
     * Get co-viewed products
     */
    protected function coViewedProducts($productIds, $limit)
    {
        return Product::whereIn('id', function ($query) use ($productIds) {
            $query->select('product_id_b')
                ->from('product_similarities')
                ->whereIn('product_id_a', $productIds)
                ->orderByDesc('similarity_score')
                ->limit($limit);
        })->get();
    }

    /**
     * Get product performance statistics
     */
    protected function getProductPerformanceStats($productId, $userId = null)
    {
        $cacheKey = "product_stats:{$productId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($productId) {
            return [
                'impressions' => PersonalizedRecommendation::where('product_id', $productId)
                    ->sum('impressions'),
                'clicks' => UserProductInteraction::where('product_id', $productId)
                    ->whereIn('interaction_type', ['click', 'view'])
                    ->count(),
                'conversions' => UserProductInteraction::where('product_id', $productId)
                    ->where('interaction_type', 'purchase')
                    ->count(),
            ];
        });
    }

    /**
     * Track impressions for bandit learning
     */
    protected function trackImpressions($products, $userId, $algorithm)
    {
        foreach ($products as $product) {
            PersonalizedRecommendation::updateOrCreate(
                [
                    'user_id' => $userId,
                    'product_id' => $product->id,
                    'recommendation_type' => $algorithm,
                ],
                [
                    'score' => $product->recommendation_score ?? 1.0,
                    'impressions' => DB::raw('impressions + 1'),
                    'last_shown_at' => now(),
                ]
            );
        }
    }

    /**
     * Track experiment exposure
     */
    protected function trackExperimentExposure($userId, $experiment, $variant)
    {
        DB::table('ab_test_exposures')->insert([
            'user_id' => $userId,
            'experiment_name' => $experiment,
            'variant' => $variant,
            'exposed_at' => now(),
        ]);
    }

    /**
     * Fallback recommendations
     */
    protected function fallbackRecommendations($userId, $limit)
    {
        $recommendationService = app(RecommendationService::class);
        return $recommendationService->getPersonalizedRecommendations($userId, $limit);
    }

    /**
     * Format recommendations
     */
    protected function formatRecommendations($predictions)
    {
        $productIds = array_column($predictions, 'product_id');
        $scores = array_column($predictions, 'score', 'product_id');

        $products = Product::whereIn('id', $productIds)->get();

        foreach ($products as $product) {
            $product->recommendation_score = $scores[$product->id] ?? 0;
        }

        return $products->sortByDesc('recommendation_score')->values();
    }
}
