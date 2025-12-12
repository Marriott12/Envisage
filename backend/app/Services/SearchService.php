<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SearchLog;
use App\Models\SearchSynonym;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SearchService
{
    protected $elasticsearchHost;
    protected $indexName = 'products';

    public function __construct()
    {
        $this->elasticsearchHost = env('ELASTICSEARCH_HOST', 'http://localhost:9200');
    }

    /**
     * Search products with advanced features
     */
    public function search($query, $options = [])
    {
        $startTime = microtime(true);

        // Extract options
        $page = $options['page'] ?? 1;
        $perPage = $options['per_page'] ?? 20;
        $filters = $options['filters'] ?? [];
        $sortBy = $options['sort_by'] ?? 'relevance';
        $userId = $options['user_id'] ?? null;
        $sessionId = $options['session_id'] ?? null;

        // Normalize and expand query
        $normalizedQuery = $this->normalizeQuery($query);
        $expandedQuery = SearchSynonym::expandQuery($normalizedQuery);

        // Try Elasticsearch first, fallback to database
        try {
            $results = $this->searchWithElasticsearch($expandedQuery, $filters, $sortBy, $page, $perPage);
        } catch (\Exception $e) {
            Log::warning("Elasticsearch search failed, falling back to database: " . $e->getMessage());
            $results = $this->searchWithDatabase($normalizedQuery, $filters, $sortBy, $page, $perPage);
        }

        // Calculate response time
        $responseTime = (microtime(true) - $startTime) * 1000;

        // Log search
        $searchLog = SearchLog::logSearch(
            $query,
            $results['total'],
            $userId,
            $sessionId,
            $filters,
            $sortBy
        );
        $searchLog->response_time_ms = round($responseTime, 2);
        $searchLog->save();

        return [
            'query' => $query,
            'results' => $results['products'],
            'total' => $results['total'],
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($results['total'] / $perPage),
            'facets' => $results['facets'] ?? [],
            'suggestions' => $this->getSuggestions($query, $results['total']),
            'response_time_ms' => round($responseTime, 2),
            'search_log_id' => $searchLog->id,
        ];
    }

    /**
     * Search using Elasticsearch
     */
    private function searchWithElasticsearch($query, $filters, $sortBy, $page, $perPage)
    {
        $from = ($page - 1) * $perPage;

        // Build Elasticsearch query
        $esQuery = [
            'from' => $from,
            'size' => $perPage,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query' => $query,
                                'fields' => ['name^3', 'description^2', 'tags^2', 'category'],
                                'type' => 'best_fields',
                                'fuzziness' => 'AUTO',
                            ]
                        ]
                    ],
                    'filter' => $this->buildFilters($filters),
                ]
            ],
            'sort' => $this->buildSort($sortBy),
            'aggs' => $this->buildAggregations(),
        ];

        // Execute search
        $response = Http::post("{$this->elasticsearchHost}/{$this->indexName}/_search", $esQuery);

        if (!$response->successful()) {
            throw new \Exception("Elasticsearch request failed");
        }

        $data = $response->json();

        // Extract products
        $productIds = collect($data['hits']['hits'])->pluck('_id');
        $products = Product::whereIn('id', $productIds)
            ->with('category')
            ->get()
            ->sortBy(function($product) use ($productIds) {
                return array_search($product->id, $productIds->toArray());
            });

        // Extract facets
        $facets = $this->extractFacets($data['aggregations'] ?? []);

        return [
            'products' => $products->values(),
            'total' => $data['hits']['total']['value'] ?? 0,
            'facets' => $facets,
        ];
    }

    /**
     * Fallback search using database
     */
    private function searchWithDatabase($query, $filters, $sortBy, $page, $perPage)
    {
        $queryObj = Product::query();

        // Full-text search simulation
        if ($query) {
            $queryObj->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('tags', 'like', "%{$query}%");
            });
        }

        // Apply filters
        if (!empty($filters['category_id'])) {
            $queryObj->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['min_price'])) {
            $queryObj->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $queryObj->where('price', '<=', $filters['max_price']);
        }

        if (!empty($filters['brand'])) {
            $queryObj->where('brand', $filters['brand']);
        }

        if (!empty($filters['in_stock'])) {
            $queryObj->where('stock_quantity', '>', 0);
        }

        // Apply sorting
        switch ($sortBy) {
            case 'price_asc':
                $queryObj->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $queryObj->orderBy('price', 'desc');
                break;
            case 'newest':
                $queryObj->orderBy('created_at', 'desc');
                break;
            case 'popular':
                $queryObj->orderBy('sales_count', 'desc');
                break;
            case 'rating':
                $queryObj->orderBy('average_rating', 'desc');
                break;
            default:
                $queryObj->orderBy('name', 'asc');
        }

        // Get total
        $total = $queryObj->count();

        // Paginate
        $products = $queryObj->with('category')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'products' => $products,
            'total' => $total,
            'facets' => [],
        ];
    }

    /**
     * Build Elasticsearch filters
     */
    private function buildFilters($filters)
    {
        $esFilters = [];

        if (!empty($filters['category_id'])) {
            $esFilters[] = ['term' => ['category_id' => $filters['category_id']]];
        }

        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $range = [];
            if (!empty($filters['min_price'])) {
                $range['gte'] = $filters['min_price'];
            }
            if (!empty($filters['max_price'])) {
                $range['lte'] = $filters['max_price'];
            }
            $esFilters[] = ['range' => ['price' => $range]];
        }

        if (!empty($filters['brand'])) {
            $esFilters[] = ['term' => ['brand.keyword' => $filters['brand']]];
        }

        if (!empty($filters['in_stock'])) {
            $esFilters[] = ['range' => ['stock_quantity' => ['gt' => 0]]];
        }

        return $esFilters;
    }

    /**
     * Build Elasticsearch sort
     */
    private function buildSort($sortBy)
    {
        switch ($sortBy) {
            case 'price_asc':
                return [['price' => 'asc']];
            case 'price_desc':
                return [['price' => 'desc']];
            case 'newest':
                return [['created_at' => 'desc']];
            case 'popular':
                return [['sales_count' => 'desc']];
            case 'rating':
                return [['average_rating' => 'desc']];
            default:
                return ['_score'];
        }
    }

    /**
     * Build aggregations for facets
     */
    private function buildAggregations()
    {
        return [
            'categories' => [
                'terms' => ['field' => 'category_id', 'size' => 20]
            ],
            'brands' => [
                'terms' => ['field' => 'brand.keyword', 'size' => 20]
            ],
            'price_ranges' => [
                'range' => [
                    'field' => 'price',
                    'ranges' => [
                        ['to' => 50],
                        ['from' => 50, 'to' => 100],
                        ['from' => 100, 'to' => 200],
                        ['from' => 200, 'to' => 500],
                        ['from' => 500],
                    ]
                ]
            ],
        ];
    }

    /**
     * Extract facets from aggregations
     */
    private function extractFacets($aggregations)
    {
        $facets = [];

        if (isset($aggregations['categories']['buckets'])) {
            $facets['categories'] = collect($aggregations['categories']['buckets'])
                ->map(fn($bucket) => [
                    'id' => $bucket['key'],
                    'count' => $bucket['doc_count']
                ]);
        }

        if (isset($aggregations['brands']['buckets'])) {
            $facets['brands'] = collect($aggregations['brands']['buckets'])
                ->map(fn($bucket) => [
                    'name' => $bucket['key'],
                    'count' => $bucket['doc_count']
                ]);
        }

        if (isset($aggregations['price_ranges']['buckets'])) {
            $facets['price_ranges'] = collect($aggregations['price_ranges']['buckets'])
                ->map(fn($bucket) => [
                    'from' => $bucket['from'] ?? 0,
                    'to' => $bucket['to'] ?? null,
                    'count' => $bucket['doc_count']
                ]);
        }

        return $facets;
    }

    /**
     * Normalize search query
     */
    private function normalizeQuery($query)
    {
        // Convert to lowercase
        $query = strtolower($query);

        // Remove special characters except spaces and hyphens
        $query = preg_replace('/[^a-z0-9\s\-]/', '', $query);

        // Remove extra spaces
        $query = preg_replace('/\s+/', ' ', $query);

        return trim($query);
    }

    /**
     * Get search suggestions
     */
    private function getSuggestions($query, $resultsCount)
    {
        $suggestions = [];

        // If no results, suggest corrections
        if ($resultsCount == 0) {
            $correction = SearchSynonym::suggestCorrection($query);
            if ($correction) {
                $suggestions[] = [
                    'type' => 'correction',
                    'text' => "Did you mean: {$correction}?",
                    'query' => $correction,
                ];
            }
        }

        // Add popular related searches
        $words = explode(' ', strtolower($query));
        if (!empty($words)) {
            $relatedSearches = SearchLog::recent(30)
                ->where(function($q) use ($words) {
                    foreach ($words as $word) {
                        $q->orWhere('query', 'like', "%{$word}%");
                    }
                })
                ->where('query', '!=', $query)
                ->where('results_count', '>', 0)
                ->groupBy('query')
                ->selectRaw('query, COUNT(*) as count')
                ->orderBy('count', 'desc')
                ->limit(3)
                ->pluck('query');

            foreach ($relatedSearches as $relatedQuery) {
                $suggestions[] = [
                    'type' => 'related',
                    'text' => $relatedQuery,
                    'query' => $relatedQuery,
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Get autocomplete suggestions
     */
    public function autocomplete($query, $limit = 5)
    {
        $query = strtolower(trim($query));

        if (strlen($query) < 2) {
            return [];
        }

        // Get from popular searches
        $suggestions = SearchLog::recent(30)
            ->where('query', 'like', "{$query}%")
            ->where('results_count', '>', 0)
            ->groupBy('query')
            ->selectRaw('query, COUNT(*) as count')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('query');

        // Also get from product names
        $products = Product::where('name', 'like', "{$query}%")
            ->orderBy('sales_count', 'desc')
            ->limit($limit)
            ->pluck('name');

        return collect($suggestions)
            ->merge($products)
            ->unique()
            ->take($limit)
            ->values();
    }

    /**
     * Index product to Elasticsearch
     */
    public function indexProduct($product)
    {
        try {
            $document = [
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'category_id' => $product->category_id,
                'category' => $product->category->name ?? '',
                'brand' => $product->brand,
                'tags' => $product->tags,
                'stock_quantity' => $product->stock_quantity,
                'sales_count' => $product->sales_count ?? 0,
                'average_rating' => $product->average_rating ?? 0,
                'created_at' => $product->created_at->toIso8601String(),
            ];

            $response = Http::put(
                "{$this->elasticsearchHost}/{$this->indexName}/_doc/{$product->id}",
                $document
            );

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Failed to index product {$product->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk index products
     */
    public function bulkIndexProducts($products = null)
    {
        if ($products === null) {
            $products = Product::with('category')->get();
        }

        $indexed = 0;
        foreach ($products as $product) {
            if ($this->indexProduct($product)) {
                $indexed++;
            }
        }

        return $indexed;
    }

    /**
     * Delete product from index
     */
    public function deleteProduct($productId)
    {
        try {
            $response = Http::delete("{$this->elasticsearchHost}/{$this->indexName}/_doc/{$productId}");
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Failed to delete product {$productId} from index: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get search analytics
     */
    public function getAnalytics($days = 30)
    {
        return SearchLog::getAnalytics($days);
    }
}
