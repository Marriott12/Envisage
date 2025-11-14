<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected $imageService;

    public function __construct(ImageUploadService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with('seller:id,name,email', 'category:id,name')
            ->where('status', 'active')
            ->where('stock', '>', 0);

        // Search
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Filter by category
        if ($request->has('category') || $request->has('category_id')) {
            $categoryValue = $request->get('category') ?? $request->get('category_id');
            if (is_numeric($categoryValue)) {
                $query->where('category_id', $categoryValue);
            } else {
                // Search by category name/slug
                $query->whereHas('category', function($q) use ($categoryValue) {
                    $q->where('name', 'like', '%' . $categoryValue . '%')
                      ->orWhere('slug', $categoryValue);
                });
            }
        }

        // Price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Condition filter
        if ($request->has('condition')) {
            $query->where('condition', $request->condition);
        }

        // Sort
        $sortBy = $request->get('sort', 'newest');
        switch ($sortBy) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'popular':
                $query->orderBy('views', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->get('per_page', 20);
        $limit = $request->get('limit', $perPage);
        
        $products = $query->paginate($limit);

        return response()->json([
            'status' => 'success',
            'message' => 'Products retrieved successfully',
            'data' => [
                'listings' => $products->items(),
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'has_more' => $products->hasMorePages()
                ]
            ]
        ]);
    }

    /**
     * Display a single product
     */
    public function show($id)
    {
        try {
            $product = Product::with('seller:id,name,email', 'category:id,name')
                ->findOrFail($id);

            // Increment view count
            $product->increment('views');

            return response()->json([
                'status' => 'success',
                'message' => 'Product retrieved successfully',
                'data' => [
                    'listing' => $product
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'condition' => 'required|in:new,used,refurbished',
            'brand' => 'nullable|string|max:255',
            'primary_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120'
        ]);

        try {
            // Handle primary image upload
            $primaryImagePath = null;
            $thumbnailPath = null;
            if ($request->hasFile('primary_image')) {
                $primaryImagePath = $this->imageService->uploadProductImages(
                    $request->file('primary_image')
                );
                $thumbnailPath = $this->imageService->createThumbnail($primaryImagePath);
            }

            // Handle additional images upload
            $imagePaths = [];
            if ($request->hasFile('images')) {
                $imagePaths = $this->imageService->uploadProductImages(
                    $request->file('images')
                );
            }

            $product = Product::create([
                'seller_id' => auth()->id(),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'stock' => $validated['stock'],
                'category_id' => $validated['category_id'] ?? null,
                'condition' => $validated['condition'],
                'brand' => $validated['brand'] ?? null,
                'primary_image' => $primaryImagePath,
                'images' => $imagePaths,
                'thumbnail' => $thumbnailPath,
                'status' => $validated['stock'] > 0 ? 'active' : 'out_of_stock'
            ]);

            return response()->json($product->load('seller:id,name,email', 'category:id,name'), 201);
        } catch (\Exception $e) {
            Log::error('Product creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Check authorization
        if ($product->seller_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'sometimes|in:draft,active,out_of_stock,archived',
            'condition' => 'sometimes|in:new,used,refurbished',
            'brand' => 'nullable|string|max:255',
            'featured' => 'sometimes|boolean',
            'primary_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'remove_images' => 'nullable|boolean'
        ]);

        try {
            // Handle primary image update
            if ($request->hasFile('primary_image')) {
                // Delete old images
                if ($product->primary_image) {
                    $this->imageService->deleteImage($product->primary_image);
                }
                if ($product->thumbnail) {
                    $this->imageService->deleteImage($product->thumbnail);
                }

                // Upload new primary image and thumbnail
                $validated['primary_image'] = $this->imageService->uploadProductImages(
                    $request->file('primary_image')
                );
                $validated['thumbnail'] = $this->imageService->createThumbnail($validated['primary_image']);
            }

            // Handle additional images update
            if ($request->hasFile('images')) {
                // Delete old images if removing
                if ($request->remove_images && $product->images) {
                    $this->imageService->deleteImage($product->images);
                }

                // Upload new images
                $newImages = $this->imageService->uploadProductImages(
                    $request->file('images')
                );

                // Merge with existing images if not removing
                if (!$request->remove_images && $product->images) {
                    $validated['images'] = array_merge($product->images, $newImages);
                } else {
                    $validated['images'] = $newImages;
                }
            }

            // Auto-update status based on stock
            if (isset($validated['stock'])) {
                if ($validated['stock'] > 0 && $product->status === 'out_of_stock') {
                    $validated['status'] = 'active';
                } elseif ($validated['stock'] === 0 && $product->status === 'active') {
                    $validated['status'] = 'out_of_stock';
                }
            }

            $product->update($validated);
            return response()->json($product->load('seller:id,name,email', 'category:id,name'));
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Check authorization
        if ($product->seller_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Delete all product images
            if ($product->primary_image) {
                $this->imageService->deleteImage($product->primary_image);
            }
            if ($product->thumbnail) {
                $this->imageService->deleteImage($product->thumbnail);
            }
            if ($product->images) {
                $this->imageService->deleteImage($product->images);
            }

            $product->delete();
            return response()->json(['message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Product deletion failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete product'], 500);
        }
    }

    /**
     * Get seller's products
     */
    public function myProducts(Request $request)
    {
        $query = Product::where('seller_id', auth()->id());

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $query->orderBy('created_at', 'desc');
        $perPage = $request->get('per_page', 20);
        
        return response()->json($query->paginate($perPage));
    }
}
