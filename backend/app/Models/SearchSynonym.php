<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchSynonym extends Model
{
    use HasFactory;

    const TYPE_SYNONYM = 'synonym';
    const TYPE_MISSPELLING = 'misspelling';
    const TYPE_ABBREVIATION = 'abbreviation';

    protected $fillable = [
        'term',
        'synonyms',
        'type',
        'is_active',
    ];

    protected $casts = [
        'synonyms' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope: Active synonyms
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By type
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get synonyms for a term
     */
    public static function getSynonymsFor($term)
    {
        $term = strtolower(trim($term));
        
        $synonym = static::active()
            ->whereRaw('LOWER(term) = ?', [$term])
            ->first();

        if ($synonym) {
            return array_merge([$term], $synonym->synonyms);
        }

        // Also check if term is in synonyms array
        $reverseLookup = static::active()
            ->whereRaw('JSON_CONTAINS(LOWER(synonyms), ?)', [json_encode($term)])
            ->first();

        if ($reverseLookup) {
            return array_merge([$reverseLookup->term], $reverseLookup->synonyms);
        }

        return [$term];
    }

    /**
     * Expand query with synonyms
     */
    public static function expandQuery($query)
    {
        $words = explode(' ', strtolower($query));
        $expandedWords = [];

        foreach ($words as $word) {
            $synonyms = static::getSynonymsFor($word);
            $expandedWords[] = implode(' OR ', $synonyms);
        }

        return implode(' AND ', $expandedWords);
    }

    /**
     * Add synonym
     */
    public static function addSynonym($term, $synonyms, $type = self::TYPE_SYNONYM)
    {
        if (!is_array($synonyms)) {
            $synonyms = [$synonyms];
        }

        $term = strtolower(trim($term));
        $synonyms = array_map('strtolower', array_map('trim', $synonyms));

        $existing = static::whereRaw('LOWER(term) = ?', [$term])->first();

        if ($existing) {
            $existing->synonyms = array_unique(array_merge($existing->synonyms, $synonyms));
            $existing->save();
            return $existing;
        }

        return static::create([
            'term' => $term,
            'synonyms' => $synonyms,
            'type' => $type,
            'is_active' => true,
        ]);
    }

    /**
     * Initialize common synonyms
     */
    public static function initializeCommonSynonyms()
    {
        $commonSynonyms = [
            // Electronics
            'phone' => ['smartphone', 'mobile', 'cellphone', 'cell'],
            'laptop' => ['notebook', 'computer', 'pc'],
            'tv' => ['television', 'screen'],
            'headphones' => ['earphones', 'earbuds', 'headset'],
            
            // Clothing
            'shirt' => ['top', 'blouse', 'tee'],
            'pants' => ['trousers', 'jeans', 'slacks'],
            'shoes' => ['footwear', 'sneakers', 'boots'],
            'dress' => ['gown', 'frock'],
            
            // Accessories
            'bag' => ['purse', 'handbag', 'backpack'],
            'watch' => ['timepiece', 'wristwatch'],
            'sunglasses' => ['shades', 'sunnies'],
            
            // Common misspellings
            'iphone' => ['i-phone', 'i phone'],
            'macbook' => ['mac book', 'mac-book'],
        ];

        foreach ($commonSynonyms as $term => $synonyms) {
            static::addSynonym($term, $synonyms);
        }

        // Add abbreviations
        $abbreviations = [
            'tv' => ['television'],
            'pc' => ['personal computer', 'computer'],
            'cpu' => ['processor', 'central processing unit'],
            'ram' => ['memory', 'random access memory'],
            'ssd' => ['solid state drive', 'storage'],
        ];

        foreach ($abbreviations as $term => $expansions) {
            static::addSynonym($term, $expansions, self::TYPE_ABBREVIATION);
        }
    }

    /**
     * Suggest correction for misspelled term
     */
    public static function suggestCorrection($term)
    {
        $term = strtolower(trim($term));

        // Check if exact match exists
        $exact = static::active()
            ->whereRaw('LOWER(term) = ?', [$term])
            ->first();

        if ($exact) {
            return null; // No correction needed
        }

        // Find similar terms using Levenshtein distance
        $allTerms = static::active()->pluck('term');
        $bestMatch = null;
        $minDistance = PHP_INT_MAX;

        foreach ($allTerms as $existingTerm) {
            $distance = levenshtein($term, strtolower($existingTerm));
            if ($distance < $minDistance && $distance <= 2) { // Max 2 character difference
                $minDistance = $distance;
                $bestMatch = $existingTerm;
            }
        }

        return $bestMatch;
    }

    /**
     * Get statistics
     */
    public static function getStatistics()
    {
        return [
            'total_synonyms' => static::active()->count(),
            'by_type' => static::active()
                ->groupBy('type')
                ->selectRaw('type, COUNT(*) as count')
                ->pluck('count', 'type'),
        ];
    }
}
