<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollaborativeFilteringData extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_type',
        'entity_id',
        'similarity_vector',
        'data_version',
        'calculated_at',
    ];

    protected $casts = [
        'similarity_vector' => 'array',
        'calculated_at' => 'datetime',
        'data_version' => 'integer',
    ];

    // Scopes
    public function scopeUserSimilarity($query)
    {
        return $query->where('data_type', 'user_similarity');
    }

    public function scopeItemSimilarity($query)
    {
        return $query->where('data_type', 'item_similarity');
    }

    public function scopeLatestVersion($query)
    {
        return $query->where('data_version', function ($subQuery) {
            $subQuery->selectRaw('MAX(data_version)')
                ->from('collaborative_filtering_data as cfd2')
                ->whereColumn('cfd2.data_type', 'collaborative_filtering_data.data_type');
        });
    }

    // Helper methods
    public static function storeSimilarityVector($type, $entityId, $vector)
    {
        $latestVersion = self::where('data_type', $type)->max('data_version') ?? 0;

        return self::updateOrCreate(
            [
                'data_type' => $type,
                'entity_id' => $entityId,
            ],
            [
                'similarity_vector' => $vector,
                'data_version' => $latestVersion + 1,
                'calculated_at' => now(),
            ]
        );
    }

    public static function getSimilarEntities($type, $entityId, $limit = 10)
    {
        $record = self::where('data_type', $type)
            ->where('entity_id', $entityId)
            ->latestVersion()
            ->first();

        if (!$record || !$record->similarity_vector) {
            return collect();
        }

        // Sort by similarity score (descending) and return top N
        arsort($record->similarity_vector);
        $similarIds = array_slice(array_keys($record->similarity_vector), 0, $limit);

        return $similarIds;
    }

    public static function calculateUserSimilarity()
    {
        // Get all users with interactions
        $users = \DB::table('user_product_interactions')
            ->distinct('user_id')
            ->pluck('user_id');

        foreach ($users as $userId) {
            // Get user's interaction vector
            $userVector = \DB::table('user_product_interactions')
                ->where('user_id', $userId)
                ->pluck('interaction_weight', 'product_id')
                ->toArray();

            // Find similar users
            $similarityScores = [];
            foreach ($users as $otherUserId) {
                if ($userId == $otherUserId) continue;

                $otherVector = \DB::table('user_product_interactions')
                    ->where('user_id', $otherUserId)
                    ->pluck('interaction_weight', 'product_id')
                    ->toArray();

                // Calculate cosine similarity
                $similarity = self::cosineSimilarity($userVector, $otherVector);
                if ($similarity > 0.1) { // Only store meaningful similarities
                    $similarityScores[$otherUserId] = $similarity;
                }
            }

            if (!empty($similarityScores)) {
                self::storeSimilarityVector('user_similarity', $userId, $similarityScores);
            }
        }
    }

    public static function calculateItemSimilarity()
    {
        // Get all products with interactions
        $products = \DB::table('user_product_interactions')
            ->distinct('product_id')
            ->pluck('product_id');

        foreach ($products as $productId) {
            // Get product's interaction vector (users who interacted)
            $productVector = \DB::table('user_product_interactions')
                ->where('product_id', $productId)
                ->pluck('interaction_weight', 'user_id')
                ->toArray();

            // Find similar products
            $similarityScores = [];
            foreach ($products as $otherProductId) {
                if ($productId == $otherProductId) continue;

                $otherVector = \DB::table('user_product_interactions')
                    ->where('product_id', $otherProductId)
                    ->pluck('interaction_weight', 'user_id')
                    ->toArray();

                // Calculate cosine similarity
                $similarity = self::cosineSimilarity($productVector, $otherVector);
                if ($similarity > 0.1) {
                    $similarityScores[$otherProductId] = $similarity;
                }
            }

            if (!empty($similarityScores)) {
                self::storeSimilarityVector('item_similarity', $productId, $similarityScores);
            }
        }
    }

    protected static function cosineSimilarity($vectorA, $vectorB)
    {
        $commonKeys = array_intersect_key($vectorA, $vectorB);
        if (empty($commonKeys)) return 0;

        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        foreach ($vectorA as $key => $valueA) {
            $valueB = $vectorB[$key] ?? 0;
            $dotProduct += $valueA * $valueB;
            $magnitudeA += $valueA * $valueA;
        }

        foreach ($vectorB as $valueB) {
            $magnitudeB += $valueB * $valueB;
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0 || $magnitudeB == 0) return 0;

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
}
