<?php

namespace App\Services;

use App\Models\ABExperiment;
use App\Models\ABTestResult;
use App\Models\User;
use App\Events\AI\ABTestWinnerDetermined;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ABTestService
{
    /**
     * Assign a user to an experiment variant
     *
     * @param string $experimentName
     * @param User|null $user
     * @return string|null Variant name
     */
    public function assignVariant($experimentName, $user = null)
    {
        $experiment = $this->getActiveExperiment($experimentName);

        if (!$experiment) {
            return null;
        }

        // Get or create user identifier
        $userId = $user ? $user->id : $this->getGuestIdentifier();

        // Check if user already assigned
        $cacheKey = "ab_test:{$experiment->id}:user:{$userId}";
        $assignedVariant = Cache::get($cacheKey);

        if ($assignedVariant) {
            return $assignedVariant;
        }

        // Assign variant based on traffic split
        $variant = $this->selectVariant($experiment->traffic_split);

        // Cache assignment for consistency
        Cache::put($cacheKey, $variant, now()->addDays(30));

        Log::info('A/B test variant assigned', [
            'experiment' => $experimentName,
            'variant' => $variant,
            'user_id' => $userId,
        ]);

        return $variant;
    }

    /**
     * Track experiment metric
     *
     * @param string $experimentName
     * @param string $metricName
     * @param float $metricValue
     * @param User|null $user
     * @param array $metadata
     * @return void
     */
    public function trackMetric($experimentName, $metricName, $metricValue, $user = null, $metadata = [])
    {
        $experiment = $this->getActiveExperiment($experimentName);

        if (!$experiment) {
            return;
        }

        $userId = $user ? $user->id : null;
        $variant = $this->assignVariant($experimentName, $user);

        if (!$variant) {
            return;
        }

        ABTestResult::create([
            'experiment_id' => $experiment->id,
            'user_id' => $userId,
            'variant' => $variant,
            'metric_name' => $metricName,
            'metric_value' => $metricValue,
            'metadata' => $metadata,
        ]);

        Log::debug('A/B test metric tracked', [
            'experiment' => $experimentName,
            'variant' => $variant,
            'metric' => $metricName,
            'value' => $metricValue,
        ]);
    }

    /**
     * Get experiment results with statistical analysis
     *
     * @param string $experimentName
     * @return array
     */
    public function getResults($experimentName)
    {
        $experiment = ABExperiment::where('name', $experimentName)->first();

        if (!$experiment) {
            return null;
        }

        $results = ABTestResult::where('experiment_id', $experiment->id)
            ->selectRaw('variant, metric_name, COUNT(*) as sample_size, AVG(metric_value) as mean, STDDEV(metric_value) as stddev')
            ->groupBy('variant', 'metric_name')
            ->get();

        $analysis = [];

        foreach ($results as $result) {
            $analysis[$result->variant][$result->metric_name] = [
                'sample_size' => $result->sample_size,
                'mean' => round($result->mean, 4),
                'stddev' => round($result->stddev ?? 0, 4),
                'confidence_interval' => $this->calculateConfidenceInterval(
                    $result->mean,
                    $result->stddev ?? 0,
                    $result->sample_size
                ),
            ];
        }

        // Determine winner
        $winner = $this->determineWinner($analysis, $experiment->primary_metric);

        return [
            'experiment' => $experiment->toArray(),
            'results' => $analysis,
            'winner' => $winner,
            'statistical_significance' => $this->calculateSignificance($analysis, $experiment->primary_metric),
        ];
    }

    /**
     * Create a new A/B test experiment
     *
     * @param array $data
     * @return ABExperiment
     */
    public function createExperiment($data)
    {
        return ABExperiment::create([
            'name' => $data['name'],
            'type' => $data['type'] ?? 'feature',
            'description' => $data['description'] ?? null,
            'variants' => $data['variants'], // ['control', 'treatment_a', 'treatment_b']
            'traffic_split' => $data['traffic_split'] ?? $this->equalSplit($data['variants']),
            'status' => 'draft',
            'primary_metric' => $data['primary_metric'] ?? 'conversion_rate',
        ]);
    }

    /**
     * Start an experiment
     *
     * @param int $experimentId
     * @return bool
     */
    public function startExperiment($experimentId)
    {
        $experiment = ABExperiment::findOrFail($experimentId);

        $experiment->update([
            'status' => 'active',
            'start_date' => now(),
        ]);

        Cache::forget("ab_experiment:{$experiment->name}");

        Log::info('A/B test started', ['experiment' => $experiment->name]);

        return true;
    }

    /**
     * Stop an experiment
     *
     * @param int $experimentId
     * @param string|null $winningVariant
     * @return bool
     */
    public function stopExperiment($experimentId, $winningVariant = null)
    {
        $experiment = ABExperiment::findOrFail($experimentId);

        // Get final metrics before stopping
        $metrics = $this->getVariantMetrics($experiment->name);
        $winnerAnalysis = $this->determineWinner($experiment->name);

        $experiment->update([
            'status' => 'completed',
            'end_date' => now(),
            'winning_variant' => $winningVariant ?? $winnerAnalysis['winner'],
        ]);

        Cache::forget("ab_experiment:{$experiment->name}");

        Log::info('A/B test stopped', [
            'experiment' => $experiment->name,
            'winner' => $winningVariant ?? $winnerAnalysis['winner'],
        ]);

        // Broadcast event to admins and data analysts
        event(new ABTestWinnerDetermined(
            $experiment->name,
            $winningVariant ?? $winnerAnalysis['winner'],
            $winnerAnalysis['is_significant'] ?? false,
            $winnerAnalysis['lift_percentage'] ?? 0,
            $winnerAnalysis['confidence_level'] ?? 0.95,
            $winnerAnalysis['metrics'] ?? []
        ));

        return true;
    }

    /**
     * Get active experiment by name
     *
     * @param string $name
     * @return ABExperiment|null
     */
    protected function getActiveExperiment($name)
    {
        return Cache::remember("ab_experiment:{$name}", 300, function () use ($name) {
            return ABExperiment::active()
                ->where('name', $name)
                ->first();
        });
    }

    /**
     * Select variant based on traffic split
     *
     * @param array $trafficSplit ['control' => 50, 'treatment' => 50]
     * @return string
     */
    protected function selectVariant($trafficSplit)
    {
        $random = mt_rand(1, 100);
        $cumulative = 0;

        foreach ($trafficSplit as $variant => $percentage) {
            $cumulative += $percentage;
            if ($random <= $cumulative) {
                return $variant;
            }
        }

        return array_key_first($trafficSplit);
    }

    /**
     * Generate equal traffic split
     *
     * @param array $variants
     * @return array
     */
    protected function equalSplit($variants)
    {
        $count = count($variants);
        $percentage = floor(100 / $count);
        $split = [];

        foreach ($variants as $variant) {
            $split[$variant] = $percentage;
        }

        return $split;
    }

    /**
     * Calculate 95% confidence interval
     *
     * @param float $mean
     * @param float $stddev
     * @param int $sampleSize
     * @return array
     */
    protected function calculateConfidenceInterval($mean, $stddev, $sampleSize)
    {
        if ($sampleSize < 2 || $stddev == 0) {
            return ['lower' => $mean, 'upper' => $mean];
        }

        $z = 1.96; // 95% confidence
        $margin = $z * ($stddev / sqrt($sampleSize));

        return [
            'lower' => round($mean - $margin, 4),
            'upper' => round($mean + $margin, 4),
        ];
    }

    /**
     * Determine winner based on primary metric
     *
     * @param array $analysis
     * @param string $metric
     * @return string|null
     */
    protected function determineWinner($analysis, $metric)
    {
        $winner = null;
        $bestValue = null;

        foreach ($analysis as $variant => $metrics) {
            if (!isset($metrics[$metric])) {
                continue;
            }

            $value = $metrics[$metric]['mean'];

            if ($bestValue === null || $value > $bestValue) {
                $bestValue = $value;
                $winner = $variant;
            }
        }

        return $winner;
    }

    /**
     * Calculate statistical significance (simplified t-test)
     *
     * @param array $analysis
     * @param string $metric
     * @return float
     */
    protected function calculateSignificance($analysis, $metric)
    {
        // Simplified - in production use proper statistical libraries
        if (count($analysis) < 2) {
            return 0;
        }

        $variants = array_values($analysis);
        if (!isset($variants[0][$metric]) || !isset($variants[1][$metric])) {
            return 0;
        }

        $a = $variants[0][$metric];
        $b = $variants[1][$metric];

        if ($a['sample_size'] < 30 || $b['sample_size'] < 30) {
            return 0; // Not enough data
        }

        // Calculate pooled standard error
        $se = sqrt(
            (($a['stddev'] ** 2) / $a['sample_size']) +
            (($b['stddev'] ** 2) / $b['sample_size'])
        );

        if ($se == 0) {
            return 0;
        }

        // Calculate t-statistic
        $t = abs($a['mean'] - $b['mean']) / $se;

        // Convert to approximate p-value (simplified)
        // p < 0.05 is considered significant
        return $t > 1.96 ? 0.95 : 0.5;
    }

    /**
     * Get guest identifier from session/cookie
     *
     * @return string
     */
    protected function getGuestIdentifier()
    {
        return session()->getId() ?? 'guest_' . request()->ip();
    }
}
