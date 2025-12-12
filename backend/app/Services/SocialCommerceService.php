<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SocialCommerceProduct;
use App\Models\SocialCommerceOrder;
use App\Models\SocialCommerceSyncLog;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SocialCommerceService
{
    /**
     * Sync product to social platform
     */
    public function syncProductToPlatform($productId, $platform)
    {
        $product = Product::with('category')->findOrFail($productId);

        // Check if already synced
        $socialProduct = SocialCommerceProduct::where('product_id', $productId)
            ->where('platform', $platform)
            ->first();

        if (!$socialProduct) {
            $socialProduct = SocialCommerceProduct::create([
                'product_id' => $productId,
                'platform' => $platform,
                'status' => SocialCommerceProduct::STATUS_PENDING,
            ]);
        }

        try {
            $platformProductId = $this->exportProductToPlatform($product, $platform);
            $socialProduct->markSynced($platformProductId);
            return $socialProduct;
        } catch (\Exception $e) {
            $socialProduct->markRejected($e->getMessage());
            Log::error("Failed to sync product {$productId} to {$platform}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export product to platform (API integration point)
     */
    private function exportProductToPlatform($product, $platform)
    {
        // Prepare product data
        $productData = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'currency' => 'USD',
            'availability' => $product->stock_quantity > 0 ? 'in_stock' : 'out_of_stock',
            'condition' => 'new',
            'image_url' => $product->image_url ?? url('/images/products/' . $product->image),
            'category' => $product->category->name ?? 'General',
        ];

        switch ($platform) {
            case SocialCommerceProduct::PLATFORM_INSTAGRAM:
                return $this->syncToInstagram($productData);
                
            case SocialCommerceProduct::PLATFORM_FACEBOOK:
                return $this->syncToFacebookMarketplace($productData);
                
            case SocialCommerceProduct::PLATFORM_TIKTOK:
                return $this->syncToTikTokShop($productData);
                
            default:
                throw new \Exception("Unsupported platform: {$platform}");
        }
    }

    /**
     * Sync to Instagram Shopping
     */
    private function syncToInstagram($productData)
    {
        $accessToken = env('INSTAGRAM_ACCESS_TOKEN');
        $catalogId = env('INSTAGRAM_CATALOG_ID');

        if (!$accessToken || !$catalogId) {
            throw new \Exception("Instagram API credentials not configured");
        }

        // Instagram Graph API endpoint
        $endpoint = "https://graph.facebook.com/v18.0/{$catalogId}/products";

        $response = Http::post($endpoint, [
            'access_token' => $accessToken,
            'retailer_id' => $productData['name'],
            'name' => $productData['name'],
            'description' => $productData['description'],
            'price' => $productData['price'] * 100, // Convert to cents
            'currency' => $productData['currency'],
            'availability' => $productData['availability'],
            'condition' => $productData['condition'],
            'image_url' => $productData['image_url'],
            'category' => $productData['category'],
        ]);

        if (!$response->successful()) {
            throw new \Exception("Instagram API error: " . $response->body());
        }

        $data = $response->json();
        return $data['id'] ?? null;
    }

    /**
     * Sync to Facebook Marketplace
     */
    private function syncToFacebookMarketplace($productData)
    {
        $accessToken = env('FACEBOOK_ACCESS_TOKEN');
        $catalogId = env('FACEBOOK_CATALOG_ID');

        if (!$accessToken || !$catalogId) {
            throw new \Exception("Facebook API credentials not configured");
        }

        $endpoint = "https://graph.facebook.com/v18.0/{$catalogId}/products";

        $response = Http::post($endpoint, [
            'access_token' => $accessToken,
            'retailer_id' => $productData['name'],
            'name' => $productData['name'],
            'description' => $productData['description'],
            'price' => $productData['price'] * 100,
            'currency' => $productData['currency'],
            'availability' => $productData['availability'],
            'condition' => $productData['condition'],
            'image_url' => $productData['image_url'],
            'category' => $productData['category'],
        ]);

        if (!$response->successful()) {
            throw new \Exception("Facebook API error: " . $response->body());
        }

        $data = $response->json();
        return $data['id'] ?? null;
    }

    /**
     * Sync to TikTok Shop
     */
    private function syncToTikTokShop($productData)
    {
        $accessToken = env('TIKTOK_ACCESS_TOKEN');
        $shopId = env('TIKTOK_SHOP_ID');

        if (!$accessToken || !$shopId) {
            throw new \Exception("TikTok API credentials not configured");
        }

        $endpoint = "https://open-api.tiktokglobalshop.com/api/products/create";

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
        ])->post($endpoint, [
            'shop_id' => $shopId,
            'product_name' => $productData['name'],
            'description' => $productData['description'],
            'price' => $productData['price'],
            'stock' => $productData['availability'] === 'in_stock' ? 100 : 0,
            'images' => [$productData['image_url']],
            'category_id' => 1, // Would need category mapping
        ]);

        if (!$response->successful()) {
            throw new \Exception("TikTok API error: " . $response->body());
        }

        $data = $response->json();
        return $data['data']['product_id'] ?? null;
    }

    /**
     * Bulk sync products to platform
     */
    public function bulkSyncProducts($platform, $productIds = null)
    {
        $syncLog = SocialCommerceSyncLog::create([
            'platform' => $platform,
            'sync_type' => SocialCommerceSyncLog::SYNC_TYPE_PRODUCTS,
            'direction' => SocialCommerceSyncLog::DIRECTION_EXPORT,
            'status' => SocialCommerceSyncLog::STATUS_PENDING,
        ]);

        try {
            $syncLog->start();

            if ($productIds === null) {
                $products = Product::where('is_active', true)->get();
            } else {
                $products = Product::whereIn('id', $productIds)->get();
            }

            $syncLog->items_total = $products->count();
            $syncLog->save();

            $successful = 0;
            $failed = 0;

            foreach ($products as $product) {
                try {
                    $this->syncProductToPlatform($product->id, $platform);
                    $successful++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to sync product {$product->id}: " . $e->getMessage());
                }

                $syncLog->updateProgress($successful + $failed, $successful, $failed);
            }

            $syncLog->complete([
                'products_synced' => $successful,
                'products_failed' => $failed,
            ]);

            return $syncLog;
        } catch (\Exception $e) {
            $syncLog->fail($e->getMessage());
            throw $e;
        }
    }

    /**
     * Update inventory on social platforms
     */
    public function updateInventory($productId, $platforms = null)
    {
        $product = Product::findOrFail($productId);

        if ($platforms === null) {
            $platforms = [
                SocialCommerceProduct::PLATFORM_INSTAGRAM,
                SocialCommerceProduct::PLATFORM_FACEBOOK,
                SocialCommerceProduct::PLATFORM_TIKTOK,
            ];
        }

        $results = [];

        foreach ($platforms as $platform) {
            $socialProduct = SocialCommerceProduct::where('product_id', $productId)
                ->where('platform', $platform)
                ->active()
                ->first();

            if (!$socialProduct || !$socialProduct->platform_product_id) {
                continue;
            }

            try {
                $this->updatePlatformInventory(
                    $platform,
                    $socialProduct->platform_product_id,
                    $product->stock_quantity
                );
                $results[$platform] = true;
            } catch (\Exception $e) {
                $results[$platform] = false;
                Log::error("Failed to update inventory for product {$productId} on {$platform}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Update inventory on specific platform
     */
    private function updatePlatformInventory($platform, $platformProductId, $quantity)
    {
        $availability = $quantity > 0 ? 'in_stock' : 'out_of_stock';

        switch ($platform) {
            case SocialCommerceProduct::PLATFORM_INSTAGRAM:
            case SocialCommerceProduct::PLATFORM_FACEBOOK:
                $accessToken = env(strtoupper($platform) . '_ACCESS_TOKEN');
                $endpoint = "https://graph.facebook.com/v18.0/{$platformProductId}";
                
                Http::post($endpoint, [
                    'access_token' => $accessToken,
                    'availability' => $availability,
                ]);
                break;

            case SocialCommerceProduct::PLATFORM_TIKTOK:
                $accessToken = env('TIKTOK_ACCESS_TOKEN');
                $endpoint = "https://open-api.tiktokglobalshop.com/api/products/stocks";
                
                Http::withHeaders([
                    'x-tts-access-token' => $accessToken,
                ])->post($endpoint, [
                    'product_id' => $platformProductId,
                    'skus' => [['sku_id' => $platformProductId, 'available_stock' => $quantity]],
                ]);
                break;
        }
    }

    /**
     * Import orders from social platforms
     */
    public function importOrders($platform, $startDate = null, $endDate = null)
    {
        $syncLog = SocialCommerceSyncLog::create([
            'platform' => $platform,
            'sync_type' => SocialCommerceSyncLog::SYNC_TYPE_ORDERS,
            'direction' => SocialCommerceSyncLog::DIRECTION_IMPORT,
            'status' => SocialCommerceSyncLog::STATUS_PENDING,
        ]);

        try {
            $syncLog->start();

            $platformOrders = $this->fetchPlatformOrders($platform, $startDate, $endDate);
            $syncLog->items_total = count($platformOrders);
            $syncLog->save();

            $successful = 0;
            $failed = 0;

            foreach ($platformOrders as $platformOrder) {
                try {
                    $this->importSingleOrder($platform, $platformOrder);
                    $successful++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to import order: " . $e->getMessage());
                }

                $syncLog->updateProgress($successful + $failed, $successful, $failed);
            }

            $syncLog->complete([
                'orders_imported' => $successful,
                'orders_failed' => $failed,
            ]);

            return $syncLog;
        } catch (\Exception $e) {
            $syncLog->fail($e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch orders from platform
     */
    private function fetchPlatformOrders($platform, $startDate, $endDate)
    {
        // Placeholder - Would integrate with actual platform APIs
        return [];
    }

    /**
     * Import single order from platform
     */
    private function importSingleOrder($platform, $platformOrderData)
    {
        // Check if already imported
        $existing = SocialCommerceOrder::where('platform_order_id', $platformOrderData['id'])->first();
        
        if ($existing && $existing->isImported()) {
            return $existing;
        }

        // Create or update social commerce order record
        $socialOrder = SocialCommerceOrder::updateOrCreate(
            ['platform_order_id' => $platformOrderData['id']],
            [
                'platform' => $platform,
                'customer_name' => $platformOrderData['customer_name'],
                'customer_email' => $platformOrderData['customer_email'],
                'customer_phone' => $platformOrderData['customer_phone'] ?? null,
                'shipping_address' => json_encode($platformOrderData['shipping_address']),
                'total_amount' => $platformOrderData['total_amount'],
                'currency' => $platformOrderData['currency'] ?? 'USD',
                'status' => SocialCommerceOrder::STATUS_PENDING,
                'platform_data' => $platformOrderData,
                'platform_created_at' => $platformOrderData['created_at'],
            ]
        );

        // Create corresponding Order in our system
        $order = Order::create([
            'user_id' => null, // Guest order
            'status' => 'pending',
            'total_amount' => $platformOrderData['total_amount'],
            'shipping_address' => json_encode($platformOrderData['shipping_address']),
            'payment_status' => 'paid', // Assume paid through platform
        ]);

        // Create order items
        foreach ($platformOrderData['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        $socialOrder->markImported($order->id);

        return $socialOrder;
    }

    /**
     * Get analytics for social commerce
     */
    public function getAnalytics($days = 30)
    {
        return [
            'products' => SocialCommerceProduct::getStatistics(),
            'orders' => SocialCommerceOrder::getStatistics(null, $days),
            'syncs' => SocialCommerceSyncLog::getStatistics(null, $days),
            'revenue_by_platform' => SocialCommerceOrder::recent($days)
                ->imported()
                ->groupBy('platform')
                ->selectRaw('platform, COUNT(*) as order_count, SUM(total_amount) as revenue')
                ->get()
                ->mapWithKeys(function($item) {
                    return [$item->platform => [
                        'orders' => $item->order_count,
                        'revenue' => $item->revenue,
                    ]];
                }),
        ];
    }

    /**
     * Remove product from platform
     */
    public function removeProductFromPlatform($productId, $platform)
    {
        $socialProduct = SocialCommerceProduct::where('product_id', $productId)
            ->where('platform', $platform)
            ->first();

        if (!$socialProduct || !$socialProduct->platform_product_id) {
            throw new \Exception("Product not found on platform");
        }

        try {
            $this->deletePlatformProduct($platform, $socialProduct->platform_product_id);
            $socialProduct->markRemoved();
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to remove product {$productId} from {$platform}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete product from platform
     */
    private function deletePlatformProduct($platform, $platformProductId)
    {
        switch ($platform) {
            case SocialCommerceProduct::PLATFORM_INSTAGRAM:
            case SocialCommerceProduct::PLATFORM_FACEBOOK:
                $accessToken = env(strtoupper($platform) . '_ACCESS_TOKEN');
                Http::delete("https://graph.facebook.com/v18.0/{$platformProductId}?access_token={$accessToken}");
                break;

            case SocialCommerceProduct::PLATFORM_TIKTOK:
                $accessToken = env('TIKTOK_ACCESS_TOKEN');
                Http::withHeaders(['x-tts-access-token' => $accessToken])
                    ->delete("https://open-api.tiktokglobalshop.com/api/products/{$platformProductId}");
                break;
        }
    }
}
