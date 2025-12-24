<?php

namespace App\Http\Controllers;

use App\Services\VisualSearchService;
use Illuminate\Http\Request;

class VisualSearchController extends Controller
{
    protected $visualSearchService;

    public function __construct(VisualSearchService $visualSearchService)
    {
        $this->visualSearchService = $visualSearchService;
    }

    /**
     * Search products by image upload
     */
    public function searchByImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
            'limit' => 'integer|min:1|max:50',
            'filters' => 'array',
        ]);

        $imageFile = $request->file('image');
        $limit = $request->input('limit', 20);
        $filters = $request->input('filters', []);

        try {
            $results = $this->visualSearchService->searchByImage($imageFile, $limit, $filters);

            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => $results->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Visual search failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detect colors in uploaded image
     */
    public function detectColors(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
            'num_colors' => 'integer|min:1|max:10',
        ]);

        $imageFile = $request->file('image');
        $numColors = $request->input('num_colors', 5);

        $colors = $this->visualSearchService->detectColors($imageFile, $numColors);

        return response()->json([
            'success' => true,
            'colors' => $colors,
        ]);
    }

    /**
     * Detect objects in image
     */
    public function detectObjects(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        $imageFile = $request->file('image');

        $detections = $this->visualSearchService->detectObjects($imageFile);

        return response()->json([
            'success' => true,
            'detections' => $detections,
        ]);
    }

    /**
     * Get style-based recommendations
     */
    public function styleRecommendations(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
            'limit' => 'integer|min:1|max:20',
        ]);

        $imageFile = $request->file('image');
        $limit = $request->input('limit', 10);

        $recommendations = $this->visualSearchService->getStyleRecommendations($imageFile, $limit);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    /**
     * Index product image for visual search
     */
    public function indexProductImage(Request $request, $productId)
    {
        $request->validate([
            'image_url' => 'required|url',
        ]);

        $imageUrl = $request->input('image_url');

        $result = $this->visualSearchService->indexProductImage($productId, $imageUrl);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Image indexed successfully' : 'Indexing failed',
        ]);
    }

    /**
     * Batch index all products
     */
    public function batchIndex(Request $request)
    {
        $batchSize = $request->input('batch_size', 50);

        $indexed = $this->visualSearchService->batchIndexProducts($batchSize);

        return response()->json([
            'success' => true,
            'indexed_count' => $indexed,
        ]);
    }
}
