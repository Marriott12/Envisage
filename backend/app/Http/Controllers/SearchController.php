<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use App\Models\SearchLog;
use App\Models\SearchSynonym;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search products
     * GET /api/search
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:500',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'sort_by' => 'string|in:relevance,price_asc,price_desc,newest,popular,rating',
            'filters' => 'array',
            'filters.category_id' => 'integer|exists:categories,id',
            'filters.min_price' => 'numeric|min:0',
            'filters.max_price' => 'numeric|min:0',
            'filters.brand' => 'string',
            'filters.in_stock' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $results = $this->searchService->search($request->q, [
            'page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 20),
            'filters' => $request->input('filters', []),
            'sort_by' => $request->input('sort_by', 'relevance'),
            'user_id' => auth()->id(),
            'session_id' => $request->session()->getId(),
        ]);

        return response()->json($results);
    }

    /**
     * Get autocomplete suggestions
     * GET /api/search/autocomplete
     */
    public function autocomplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'limit' => 'integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $suggestions = $this->searchService->autocomplete(
            $request->q,
            $request->input('limit', 5)
        );

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Log search click
     * POST /api/search/log-click
     */
    public function logClick(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search_log_id' => 'required|exists:search_logs,id',
            'product_id' => 'required|exists:products,id',
            'position' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $searchLog = SearchLog::find($request->search_log_id);
        $searchLog->logClick($request->product_id, $request->position);

        return response()->json(['success' => true]);
    }

    /**
     * Get search analytics
     * GET /api/search/analytics
     */
    public function getAnalytics(Request $request)
    {
        $days = $request->input('days', 30);
        $analytics = $this->searchService->getAnalytics($days);

        return response()->json($analytics);
    }

    /**
     * Get popular searches
     * GET /api/search/popular
     */
    public function getPopularSearches(Request $request)
    {
        $limit = $request->input('limit', 10);
        $days = $request->input('days', 7);

        $popular = SearchLog::getPopularSearches($limit, $days);

        return response()->json(['popular_searches' => $popular]);
    }

    /**
     * Get searches with no results
     * GET /api/search/no-results
     */
    public function getNoResultSearches(Request $request)
    {
        $limit = $request->input('limit', 20);
        $days = $request->input('days', 7);

        $noResults = SearchLog::getNoResultSearches($limit, $days);

        return response()->json(['no_result_searches' => $noResults]);
    }

    // ==================== SYNONYM MANAGEMENT (ADMIN) ====================

    /**
     * List synonyms
     * GET /api/search/synonyms
     */
    public function listSynonyms(Request $request)
    {
        $query = SearchSynonym::query();

        if ($request->has('active_only')) {
            $query->active();
        }

        if ($request->has('type')) {
            $query->type($request->type);
        }

        $synonyms = $query->orderBy('term')
            ->paginate($request->input('per_page', 50));

        return response()->json($synonyms);
    }

    /**
     * Create synonym
     * POST /api/search/synonyms
     */
    public function createSynonym(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'term' => 'required|string|max:100',
            'synonyms' => 'required|array|min:1',
            'synonyms.*' => 'string|max:100',
            'type' => 'required|in:synonym,misspelling,abbreviation',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $synonym = SearchSynonym::addSynonym(
            $request->term,
            $request->synonyms,
            $request->type
        );

        return response()->json($synonym, 201);
    }

    /**
     * Update synonym
     * PUT /api/search/synonyms/{id}
     */
    public function updateSynonym(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'term' => 'string|max:100',
            'synonyms' => 'array|min:1',
            'synonyms.*' => 'string|max:100',
            'type' => 'in:synonym,misspelling,abbreviation',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $synonym = SearchSynonym::findOrFail($id);
        $synonym->update($request->all());

        return response()->json($synonym);
    }

    /**
     * Delete synonym
     * DELETE /api/search/synonyms/{id}
     */
    public function deleteSynonym($id)
    {
        $synonym = SearchSynonym::findOrFail($id);
        $synonym->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Initialize common synonyms
     * POST /api/search/synonyms/initialize
     */
    public function initializeSynonyms()
    {
        SearchSynonym::initializeCommonSynonyms();

        return response()->json([
            'success' => true,
            'message' => 'Common synonyms initialized',
        ]);
    }

    /**
     * Get synonym statistics
     * GET /api/search/synonyms/statistics
     */
    public function getSynonymStatistics()
    {
        $stats = SearchSynonym::getStatistics();
        return response()->json($stats);
    }

    // ==================== ELASTICSEARCH INDEX MANAGEMENT (ADMIN) ====================

    /**
     * Index product to Elasticsearch
     * POST /api/search/index/product/{productId}
     */
    public function indexProduct($productId)
    {
        $product = \App\Models\Product::findOrFail($productId);
        $success = $this->searchService->indexProduct($product);

        if ($success) {
            return response()->json(['success' => true, 'message' => 'Product indexed successfully']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to index product'], 500);
    }

    /**
     * Bulk index all products
     * POST /api/search/index/bulk
     */
    public function bulkIndexProducts()
    {
        $indexed = $this->searchService->bulkIndexProducts();

        return response()->json([
            'success' => true,
            'indexed_count' => $indexed,
        ]);
    }

    /**
     * Delete product from index
     * DELETE /api/search/index/product/{productId}
     */
    public function deleteProductFromIndex($productId)
    {
        $success = $this->searchService->deleteProduct($productId);

        if ($success) {
            return response()->json(['success' => true, 'message' => 'Product removed from index']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to remove product from index'], 500);
    }
}
