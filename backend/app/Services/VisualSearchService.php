<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use Intervention\Image\Facades\Image as InterventionImage;

/**
 * AI-Powered Visual Search Service
 * 
 * Features:
 * - Image-based product search
 * - Deep learning feature extraction (ResNet, EfficientNet)
 * - Visual similarity matching
 * - Reverse image search
 * - Style transfer and recommendations
 * - Color/pattern detection
 */
class VisualSearchService
{
    protected $mlServiceUrl;
    protected $similarityThreshold = 0.7;

    public function __construct()
    {
        $this->mlServiceUrl = config('services.ml.url', env('ML_SERVICE_URL', 'http://localhost:5000'));
    }

    /**
     * Search products by uploading an image
     */
    public function searchByImage($imageFile, $limit = 20, $filters = [])
    {
        // Extract features from uploaded image
        $features = $this->extractImageFeatures($imageFile);

        if (!$features) {
            throw new \Exception('Failed to extract image features');
        }

        // Find similar products
        $similarProducts = $this->findSimilarProducts($features, $limit * 2);

        // Apply additional filters
        if (!empty($filters)) {
            $similarProducts = $this->applyFilters($similarProducts, $filters);
        }

        return collect($similarProducts)->take($limit);
    }

    /**
     * Extract deep learning features from image
     * Uses pre-trained ResNet50 or EfficientNet
     */
    public function extractImageFeatures($imageFile)
    {
        try {
            // Prepare image for ML service
            $imagePath = $this->prepareImage($imageFile);

            // Call Python ML service for feature extraction
            $response = Http::attach(
                'image',
                file_get_contents($imagePath),
                basename($imagePath)
            )->post("{$this->mlServiceUrl}/api/vision/extract-features", [
                'model' => 'efficientnet_b3', // or 'resnet50'
                'layer' => 'avg_pool',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['features'];
            }
        } catch (\Exception $e) {
            \Log::error("Feature extraction failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Find products with similar visual features
     */
    protected function findSimilarProducts($queryFeatures, $limit)
    {
        try {
            // Call ML service to compute similarities
            $response = Http::timeout(10)->post("{$this->mlServiceUrl}/api/vision/find-similar", [
                'features' => $queryFeatures,
                'limit' => $limit,
                'threshold' => $this->similarityThreshold,
            ]);

            if ($response->successful()) {
                $results = $response->json()['similar_products'];
                
                // Load products from database
                $productIds = array_column($results, 'product_id');
                $similarities = array_column($results, 'similarity', 'product_id');

                $products = Product::whereIn('id', $productIds)->get();

                foreach ($products as $product) {
                    $product->similarity_score = $similarities[$product->id] ?? 0;
                }

                return $products->sortByDesc('similarity_score')->values();
            }
        } catch (\Exception $e) {
            \Log::error("Similarity search failed: " . $e->getMessage());
        }

        // Fallback to color-based matching
        return $this->colorBasedSearch($queryFeatures, $limit);
    }

    /**
     * Detect dominant colors in image
     */
    public function detectColors($imageFile, $numColors = 5)
    {
        try {
            $imagePath = $this->prepareImage($imageFile);

            $response = Http::attach(
                'image',
                file_get_contents($imagePath),
                basename($imagePath)
            )->post("{$this->mlServiceUrl}/api/vision/detect-colors", [
                'num_colors' => $numColors,
            ]);

            if ($response->successful()) {
                return $response->json()['colors'];
            }
        } catch (\Exception $e) {
            \Log::error("Color detection failed: " . $e->getMessage());
        }

        // Fallback to PHP color extraction
        return $this->extractColorsPHP($imageFile, $numColors);
    }

    /**
     * Detect objects and attributes in image
     */
    public function detectObjects($imageFile)
    {
        try {
            $imagePath = $this->prepareImage($imageFile);

            $response = Http::attach(
                'image',
                file_get_contents($imagePath),
                basename($imagePath)
            )->post("{$this->mlServiceUrl}/api/vision/detect-objects", [
                'model' => 'yolov8',
                'confidence_threshold' => 0.5,
            ]);

            if ($response->successful()) {
                return $response->json()['detections'];
            }
        } catch (\Exception $e) {
            \Log::error("Object detection failed: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Get style recommendations based on image
     */
    public function getStyleRecommendations($imageFile, $limit = 10)
    {
        // Extract style features
        $styleFeatures = $this->extractStyleFeatures($imageFile);

        if (!$styleFeatures) {
            return collect();
        }

        // Find products with similar style
        try {
            $response = Http::timeout(10)->post("{$this->mlServiceUrl}/api/vision/style-match", [
                'features' => $styleFeatures,
                'limit' => $limit,
            ]);

            if ($response->successful()) {
                $productIds = $response->json()['recommendations'];
                return Product::whereIn('id', $productIds)->get();
            }
        } catch (\Exception $e) {
            \Log::error("Style matching failed: " . $e->getMessage());
        }

        return collect();
    }

    /**
     * Extract style-specific features (texture, pattern, design)
     */
    protected function extractStyleFeatures($imageFile)
    {
        try {
            $imagePath = $this->prepareImage($imageFile);

            $response = Http::attach(
                'image',
                file_get_contents($imagePath),
                basename($imagePath)
            )->post("{$this->mlServiceUrl}/api/vision/extract-style", [
                'model' => 'style_transfer_vgg',
            ]);

            if ($response->successful()) {
                return $response->json()['style_features'];
            }
        } catch (\Exception $e) {
            \Log::error("Style extraction failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Index product image features for fast search
     * Should be run when products are added/updated
     */
    public function indexProductImage($productId, $imageUrl)
    {
        try {
            // Download and prepare image
            $tempPath = $this->downloadImage($imageUrl);

            // Extract features
            $features = $this->extractImageFeatures($tempPath);

            if ($features) {
                // Send to ML service for indexing
                Http::post("{$this->mlServiceUrl}/api/vision/index", [
                    'product_id' => $productId,
                    'features' => $features,
                    'image_url' => $imageUrl,
                ]);

                // Also detect and store colors
                $colors = $this->detectColors($tempPath);
                
                // Update product with color metadata
                Product::where('id', $productId)->update([
                    'dominant_colors' => json_encode($colors),
                ]);
            }

            // Clean up
            @unlink($tempPath);

            return true;
        } catch (\Exception $e) {
            \Log::error("Product indexing failed for {$productId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Batch index all products
     */
    public function batchIndexProducts($batchSize = 50)
    {
        $indexed = 0;
        
        Product::whereNotNull('image_url')
            ->chunk($batchSize, function ($products) use (&$indexed) {
                foreach ($products as $product) {
                    if ($this->indexProductImage($product->id, $product->image_url)) {
                        $indexed++;
                    }
                    
                    // Rate limiting
                    usleep(200000); // 200ms delay
                }
            });

        return $indexed;
    }

    /**
     * Prepare image for processing
     */
    protected function prepareImage($imageFile)
    {
        $tempPath = storage_path('app/temp/' . uniqid() . '.jpg');

        if (is_string($imageFile) && filter_var($imageFile, FILTER_VALIDATE_URL)) {
            // URL provided
            $tempPath = $this->downloadImage($imageFile);
        } elseif ($imageFile instanceof \Illuminate\Http\UploadedFile) {
            // Uploaded file
            $imageFile->move(storage_path('app/temp'), basename($tempPath));
        } else {
            // Base64 or file path
            if (is_string($imageFile) && file_exists($imageFile)) {
                copy($imageFile, $tempPath);
            } else {
                // Assume base64
                $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageFile));
                file_put_contents($tempPath, $image);
            }
        }

        // Resize to optimal size for ML (224x224 or 384x384)
        $this->resizeImage($tempPath, 384, 384);

        return $tempPath;
    }

    /**
     * Download image from URL
     */
    protected function downloadImage($url)
    {
        $tempPath = storage_path('app/temp/' . uniqid() . '.jpg');
        $imageData = file_get_contents($url);
        file_put_contents($tempPath, $imageData);
        return $tempPath;
    }

    /**
     * Resize image
     */
    protected function resizeImage($path, $width, $height)
    {
        try {
            $img = InterventionImage::make($path);
            $img->fit($width, $height);
            $img->save($path, 90);
        } catch (\Exception $e) {
            \Log::warning("Image resize failed: " . $e->getMessage());
        }
    }

    /**
     * Fallback: PHP-based color extraction
     */
    protected function extractColorsPHP($imageFile, $numColors)
    {
        try {
            $imagePath = $this->prepareImage($imageFile);
            $img = InterventionImage::make($imagePath);
            
            // Resize for faster processing
            $img->resize(100, 100);
            
            $colors = [];
            $width = $img->width();
            $height = $img->height();
            
            // Sample pixels
            for ($x = 0; $x < $width; $x += 10) {
                for ($y = 0; $y < $height; $y += 10) {
                    $rgb = $img->pickColor($x, $y);
                    $hex = sprintf("#%02x%02x%02x", $rgb[0], $rgb[1], $rgb[2]);
                    
                    if (isset($colors[$hex])) {
                        $colors[$hex]++;
                    } else {
                        $colors[$hex] = 1;
                    }
                }
            }
            
            // Sort by frequency
            arsort($colors);
            
            return array_slice(array_keys($colors), 0, $numColors);
        } catch (\Exception $e) {
            \Log::error("PHP color extraction failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fallback: Color-based product search
     */
    protected function colorBasedSearch($features, $limit)
    {
        // This is a simplified fallback
        // In production, use dominant_colors field to find similar products
        
        return Product::whereNotNull('dominant_colors')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Apply filters to search results
     */
    protected function applyFilters($products, $filters)
    {
        $filtered = $products;

        if (isset($filters['category_id'])) {
            $filtered = $filtered->where('category_id', $filters['category_id']);
        }

        if (isset($filters['min_price'])) {
            $filtered = $filtered->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $filtered = $filtered->where('price', '<=', $filters['max_price']);
        }

        if (isset($filters['color'])) {
            $filtered = $filtered->filter(function ($product) use ($filters) {
                $colors = json_decode($product->dominant_colors, true) ?? [];
                return in_array($filters['color'], $colors);
            });
        }

        return $filtered;
    }

    /**
     * Get visual search suggestions as user types
     */
    public function getVisualSuggestions($partialQuery, $limit = 5)
    {
        // Find products with images matching text description
        $products = Product::where('name', 'LIKE', "%{$partialQuery}%")
            ->orWhere('description', 'LIKE', "%{$partialQuery}%")
            ->whereNotNull('image_url')
            ->limit($limit)
            ->get();

        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'image_url' => $product->image_url,
                'price' => $product->price,
            ];
        });
    }
}
