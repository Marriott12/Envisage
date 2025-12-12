<?php

namespace App\Http\Controllers;

use App\Services\SocialCommerceService;
use App\Models\SocialCommerceProduct;
use App\Models\SocialCommerceOrder;
use App\Models\SocialCommerceSyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SocialCommerceController extends Controller
{
    protected $service;

    public function __construct(SocialCommerceService $service)
    {
        $this->service = $service;
    }

    // ==================== PRODUCT SYNC ====================

    /**
     * List synced products
     * GET /api/social-commerce/products
     */
    public function listProducts(Request $request)
    {
        $query = SocialCommerceProduct::with('product');

        if ($request->has('platform')) {
            $query->platform($request->platform);
        }

        if ($request->has('status')) {
            $query->status($request->status);
        }

        if ($request->has('needs_update')) {
            $query->needsUpdate();
        }

        $products = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($products);
    }

    /**
     * Get product sync status
     * GET /api/social-commerce/products/{id}
     */
    public function getProduct($id)
    {
        $product = SocialCommerceProduct::with('product')->findOrFail($id);
        
        return response()->json([
            'product' => $product,
            'needs_sync' => $product->needsSync(),
            'platform_url' => $product->platform_url,
        ]);
    }

    /**
     * Sync product to platform
     * POST /api/social-commerce/products/sync
     */
    public function syncProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'platform' => 'required|in:instagram,facebook,tiktok',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $socialProduct = $this->service->syncProductToPlatform(
                $request->product_id,
                $request->platform
            );

            return response()->json($socialProduct);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to sync product',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk sync products
     * POST /api/social-commerce/products/bulk-sync
     */
    public function bulkSyncProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:instagram,facebook,tiktok',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $syncLog = $this->service->bulkSyncProducts(
                $request->platform,
                $request->input('product_ids')
            );

            return response()->json($syncLog);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Bulk sync failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update inventory on platforms
     * POST /api/social-commerce/products/{productId}/update-inventory
     */
    public function updateInventory(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'platforms' => 'nullable|array',
            'platforms.*' => 'in:instagram,facebook,tiktok',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $results = $this->service->updateInventory(
                $productId,
                $request->input('platforms')
            );

            return response()->json(['results' => $results]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update inventory',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove product from platform
     * DELETE /api/social-commerce/products/{productId}/platform/{platform}
     */
    public function removeProduct($productId, $platform)
    {
        try {
            $this->service->removeProductFromPlatform($productId, $platform);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove product',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product statistics
     * GET /api/social-commerce/products/statistics
     */
    public function getProductStatistics(Request $request)
    {
        $platform = $request->input('platform');
        $stats = SocialCommerceProduct::getStatistics($platform);

        return response()->json($stats);
    }

    // ==================== ORDER MANAGEMENT ====================

    /**
     * List social commerce orders
     * GET /api/social-commerce/orders
     */
    public function listOrders(Request $request)
    {
        $query = SocialCommerceOrder::with('order');

        if ($request->has('platform')) {
            $query->platform($request->platform);
        }

        if ($request->has('status')) {
            $query->status($request->status);
        }

        if ($request->has('pending_only')) {
            $query->pending();
        }

        $orders = $query->orderBy('platform_created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($orders);
    }

    /**
     * Get order details
     * GET /api/social-commerce/orders/{id}
     */
    public function getOrder($id)
    {
        $order = SocialCommerceOrder::with('order.items.product')->findOrFail($id);

        return response()->json([
            'order' => $order,
            'is_imported' => $order->isImported(),
            'platform_url' => $order->platform_order_url,
            'items' => $order->getItemsFromPlatformData(),
        ]);
    }

    /**
     * Import orders from platform
     * POST /api/social-commerce/orders/import
     */
    public function importOrders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:instagram,facebook,tiktok',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $syncLog = $this->service->importOrders(
                $request->platform,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return response()->json($syncLog);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to import orders',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark order as completed
     * POST /api/social-commerce/orders/{id}/complete
     */
    public function completeOrder($id)
    {
        $order = SocialCommerceOrder::findOrFail($id);
        $order->markCompleted();

        return response()->json($order);
    }

    /**
     * Get order statistics
     * GET /api/social-commerce/orders/statistics
     */
    public function getOrderStatistics(Request $request)
    {
        $platform = $request->input('platform');
        $days = $request->input('days', 30);
        $stats = SocialCommerceOrder::getStatistics($platform, $days);

        return response()->json($stats);
    }

    // ==================== SYNC LOGS ====================

    /**
     * List sync logs
     * GET /api/social-commerce/sync-logs
     */
    public function listSyncLogs(Request $request)
    {
        $query = SocialCommerceSyncLog::query();

        if ($request->has('platform')) {
            $query->platform($request->platform);
        }

        if ($request->has('sync_type')) {
            $query->syncType($request->sync_type);
        }

        if ($request->has('direction')) {
            $query->direction($request->direction);
        }

        if ($request->has('status')) {
            $query->status($request->status);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($logs);
    }

    /**
     * Get sync log details
     * GET /api/social-commerce/sync-logs/{id}
     */
    public function getSyncLog($id)
    {
        $log = SocialCommerceSyncLog::findOrFail($id);

        return response()->json([
            'log' => $log,
            'duration' => $log->duration,
            'success_rate' => $log->success_rate,
            'is_successful' => $log->isSuccessful(),
            'is_partially_successful' => $log->isPartiallySuccessful(),
        ]);
    }

    /**
     * Get sync statistics
     * GET /api/social-commerce/sync-logs/statistics
     */
    public function getSyncStatistics(Request $request)
    {
        $platform = $request->input('platform');
        $days = $request->input('days', 30);
        $stats = SocialCommerceSyncLog::getStatistics($platform, $days);

        return response()->json($stats);
    }

    /**
     * Check if sync is due
     * GET /api/social-commerce/sync-logs/is-due
     */
    public function isSyncDue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:instagram,facebook,tiktok',
            'sync_type' => 'required|in:products,inventory,orders,catalog',
            'hours_interval' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $isDue = SocialCommerceSyncLog::isSyncDue(
            $request->platform,
            $request->sync_type,
            $request->input('hours_interval', 24)
        );

        $lastSync = SocialCommerceSyncLog::getLatestSync($request->platform, $request->sync_type);

        return response()->json([
            'is_due' => $isDue,
            'last_sync' => $lastSync,
        ]);
    }

    // ==================== ANALYTICS ====================

    /**
     * Get comprehensive analytics
     * GET /api/social-commerce/analytics
     */
    public function getAnalytics(Request $request)
    {
        $days = $request->input('days', 30);
        $analytics = $this->service->getAnalytics($days);

        return response()->json($analytics);
    }

    /**
     * Get platform comparison
     * GET /api/social-commerce/analytics/platform-comparison
     */
    public function getPlatformComparison(Request $request)
    {
        $days = $request->input('days', 30);

        $comparison = [
            'instagram' => [
                'products' => SocialCommerceProduct::platform('instagram')->count(),
                'active_products' => SocialCommerceProduct::platform('instagram')->active()->count(),
                'orders' => SocialCommerceOrder::platform('instagram')->recent($days)->count(),
                'revenue' => SocialCommerceOrder::platform('instagram')->recent($days)->imported()->sum('total_amount'),
            ],
            'facebook' => [
                'products' => SocialCommerceProduct::platform('facebook')->count(),
                'active_products' => SocialCommerceProduct::platform('facebook')->active()->count(),
                'orders' => SocialCommerceOrder::platform('facebook')->recent($days)->count(),
                'revenue' => SocialCommerceOrder::platform('facebook')->recent($days)->imported()->sum('total_amount'),
            ],
            'tiktok' => [
                'products' => SocialCommerceProduct::platform('tiktok')->count(),
                'active_products' => SocialCommerceProduct::platform('tiktok')->active()->count(),
                'orders' => SocialCommerceOrder::platform('tiktok')->recent($days)->count(),
                'revenue' => SocialCommerceOrder::platform('tiktok')->recent($days)->imported()->sum('total_amount'),
            ],
        ];

        return response()->json($comparison);
    }
}
