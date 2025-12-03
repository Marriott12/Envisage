<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    // Get seller dashboard overview
    public function sellerDashboard(Request $request)
    {
        $sellerId = $request->user()->id;
        $period = $request->input('period', '30'); // days

        $startDate = Carbon::now()->subDays($period);

        // Total sales and revenue
        $salesData = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(DISTINCT orders.id) as total_orders,
                SUM(order_items.quantity) as total_items_sold,
                SUM(order_items.quantity * order_items.price) as total_revenue
            ')
            ->first();

        // Revenue trend (daily)
        $revenueTrend = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('
                DATE(orders.created_at) as date,
                SUM(order_items.quantity * order_items.price) as revenue,
                COUNT(DISTINCT orders.id) as orders
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top performing products
        $topProducts = Product::where('seller_id', $sellerId)
            ->withCount(['orderItems as sales_count' => function($query) use ($startDate) {
                $query->whereHas('order', function($q) use ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                });
            }])
            ->withSum(['orderItems as revenue' => function($query) use ($startDate) {
                $query->whereHas('order', function($q) use ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                })->selectRaw('SUM(quantity * price)');
            }], 'quantity')
            ->having('sales_count', '>', 0)
            ->orderBy('sales_count', 'desc')
            ->limit(10)
            ->get();

        // Category performance
        $categoryPerformance = Product::where('seller_id', $sellerId)
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('
                products.category_id,
                COUNT(DISTINCT order_items.id) as items_sold,
                SUM(order_items.quantity * order_items.price) as revenue
            ')
            ->groupBy('products.category_id')
            ->orderBy('revenue', 'desc')
            ->get();

        // Order status breakdown
        $orderStatusBreakdown = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('
                orders.status,
                COUNT(DISTINCT orders.id) as count
            ')
            ->groupBy('orders.status')
            ->get();

        // Recent orders
        $recentOrders = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $sellerId)
            ->select('orders.*')
            ->distinct()
            ->orderBy('orders.created_at', 'desc')
            ->limit(10)
            ->with('user', 'orderItems.product')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_orders' => $salesData->total_orders ?? 0,
                    'total_items_sold' => $salesData->total_items_sold ?? 0,
                    'total_revenue' => $salesData->total_revenue ?? 0,
                ],
                'revenue_trend' => $revenueTrend,
                'top_products' => $topProducts,
                'category_performance' => $categoryPerformance,
                'order_status_breakdown' => $orderStatusBreakdown,
                'recent_orders' => $recentOrders,
            ],
        ]);
    }

    // Get customer analytics
    public function customerAnalytics(Request $request)
    {
        $sellerId = $request->user()->id;
        $period = $request->input('period', '30');
        $startDate = Carbon::now()->subDays($period);

        // Top customers by revenue
        $topCustomers = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('
                users.id,
                users.name,
                users.email,
                COUNT(DISTINCT orders.id) as total_orders,
                SUM(order_items.quantity * order_items.price) as total_spent
            ')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get();

        // New vs returning customers
        $customerBreakdown = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(DISTINCT CASE WHEN (
                    SELECT COUNT(*) FROM orders o2
                    JOIN order_items oi2 ON o2.id = oi2.order_id
                    JOIN products p2 ON oi2.product_id = p2.id
                    WHERE o2.user_id = orders.user_id
                    AND p2.seller_id = products.seller_id
                    AND o2.created_at < orders.created_at
                ) = 0 THEN orders.user_id END) as new_customers,
                COUNT(DISTINCT CASE WHEN (
                    SELECT COUNT(*) FROM orders o2
                    JOIN order_items oi2 ON o2.id = oi2.order_id
                    JOIN products p2 ON oi2.product_id = p2.id
                    WHERE o2.user_id = orders.user_id
                    AND p2.seller_id = products.seller_id
                    AND o2.created_at < orders.created_at
                ) > 0 THEN orders.user_id END) as returning_customers
            ')
            ->first();

        // Customer acquisition trend
        $acquisitionTrend = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('
                DATE(orders.created_at) as date,
                COUNT(DISTINCT orders.user_id) as new_customers
            ')
            ->whereRaw('(
                SELECT COUNT(*) FROM orders o2
                JOIN order_items oi2 ON o2.id = oi2.order_id
                JOIN products p2 ON oi2.product_id = p2.id
                WHERE o2.user_id = orders.user_id
                AND p2.seller_id = ?
                AND o2.created_at < orders.created_at
            ) = 0', [$sellerId])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'top_customers' => $topCustomers,
                'customer_breakdown' => $customerBreakdown,
                'acquisition_trend' => $acquisitionTrend,
            ],
        ]);
    }

    // Get product performance details
    public function productPerformance(Request $request, $productId)
    {
        $sellerId = $request->user()->id;
        $product = Product::where('seller_id', $sellerId)->findOrFail($productId);
        
        $period = $request->input('period', '30');
        $startDate = Carbon::now()->subDays($period);

        // Sales metrics
        $metrics = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('
                SUM(order_items.quantity) as units_sold,
                SUM(order_items.quantity * order_items.price) as revenue,
                COUNT(DISTINCT orders.id) as orders_count,
                AVG(order_items.quantity) as avg_quantity_per_order
            ')
            ->first();

        // Sales trend
        $salesTrend = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('
                DATE(orders.created_at) as date,
                SUM(order_items.quantity) as units,
                SUM(order_items.quantity * order_items.price) as revenue
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // View to purchase conversion
        $views = DB::table('product_views')
            ->where('product_id', $productId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $conversionRate = $views > 0 ? ($metrics->orders_count / $views) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'metrics' => [
                    'units_sold' => $metrics->units_sold ?? 0,
                    'revenue' => $metrics->revenue ?? 0,
                    'orders_count' => $metrics->orders_count ?? 0,
                    'avg_quantity_per_order' => round($metrics->avg_quantity_per_order ?? 0, 2),
                    'views' => $views,
                    'conversion_rate' => round($conversionRate, 2),
                ],
                'sales_trend' => $salesTrend,
            ],
        ]);
    }

    // Export analytics data
    public function exportData(Request $request)
    {
        $sellerId = $request->user()->id;
        $type = $request->input('type', 'sales'); // sales, customers, products
        $period = $request->input('period', '30');
        $startDate = Carbon::now()->subDays($period);

        $data = [];

        switch ($type) {
            case 'sales':
                $data = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->join('users', 'orders.user_id', '=', 'users.id')
                    ->where('products.seller_id', $sellerId)
                    ->where('orders.created_at', '>=', $startDate)
                    ->select(
                        'orders.id as order_id',
                        'orders.created_at',
                        'orders.status',
                        'users.name as customer_name',
                        'users.email as customer_email',
                        'products.name as product_name',
                        'order_items.quantity',
                        'order_items.price',
                        DB::raw('order_items.quantity * order_items.price as total')
                    )
                    ->orderBy('orders.created_at', 'desc')
                    ->get();
                break;

            case 'customers':
                $data = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->join('users', 'orders.user_id', '=', 'users.id')
                    ->where('products.seller_id', $sellerId)
                    ->where('orders.created_at', '>=', $startDate)
                    ->selectRaw('
                        users.id,
                        users.name,
                        users.email,
                        COUNT(DISTINCT orders.id) as total_orders,
                        SUM(order_items.quantity) as total_items,
                        SUM(order_items.quantity * order_items.price) as total_spent
                    ')
                    ->groupBy('users.id', 'users.name', 'users.email')
                    ->orderBy('total_spent', 'desc')
                    ->get();
                break;

            case 'products':
                $data = Product::where('seller_id', $sellerId)
                    ->leftJoin('order_items', function($join) use ($startDate) {
                        $join->on('products.id', '=', 'order_items.product_id')
                            ->join('orders', 'order_items.order_id', '=', 'orders.id')
                            ->where('orders.created_at', '>=', $startDate);
                    })
                    ->selectRaw('
                        products.id,
                        products.name,
                        products.price,
                        products.stock_quantity,
                        COUNT(order_items.id) as sales_count,
                        SUM(order_items.quantity) as units_sold,
                        SUM(order_items.quantity * order_items.price) as revenue
                    ')
                    ->groupBy('products.id', 'products.name', 'products.price', 'products.stock_quantity')
                    ->orderBy('revenue', 'desc')
                    ->get();
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'type' => $type,
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => Carbon::now()->toDateString(),
                'count' => count($data),
            ],
        ]);
    }
}
