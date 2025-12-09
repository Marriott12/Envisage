<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\FlashSale;
use App\Models\SubscriptionPlan;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Get all disputes with optional filters
     */
    public function disputes(Request $request)
    {
        try {
            $query = Dispute::with(['order', 'user']);

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%");
                    })->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            }

            $disputes = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $disputes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch disputes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update dispute status and admin response
     */
    public function updateDispute($id, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,approved,rejected,resolved,escalated',
                'admin_response' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dispute = Dispute::with(['order', 'user'])->findOrFail($id);
            
            $dispute->status = $request->status;
            if ($request->has('admin_response')) {
                $dispute->admin_response = $request->admin_response;
            }
            $dispute->save();

            // TODO: Send notification to user about dispute update
            // event(new DisputeUpdated($dispute));

            return response()->json([
                'success' => true,
                'data' => $dispute,
                'message' => 'Dispute updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update dispute',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all flash sales
     */
    public function flashSales(Request $request)
    {
        try {
            $flashSales = FlashSale::with(['products.product'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $flashSales
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch flash sales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive analytics data
     */
    public function analytics(Request $request)
    {
        try {
            $range = $request->get('range', '30d');
            
            // Calculate date range
            switch ($range) {
                case '7d':
                    $startDate = Carbon::now()->subDays(7);
                    break;
                case '30d':
                    $startDate = Carbon::now()->subDays(30);
                    break;
                case '90d':
                    $startDate = Carbon::now()->subDays(90);
                    break;
                default:
                    $startDate = Carbon::now()->subDays(30);
            }

            switch ($range) {
                case '7d':
                    $previousStartDate = Carbon::now()->subDays(14);
                    break;
                case '30d':
                    $previousStartDate = Carbon::now()->subDays(60);
                    break;
                case '90d':
                    $previousStartDate = Carbon::now()->subDays(180);
                    break;
                default:
                    $previousStartDate = Carbon::now()->subDays(60);
            }

            // Revenue metrics
            $currentRevenue = Order::where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            $previousRevenue = Order::where('created_at', '>=', $previousStartDate)
                ->where('created_at', '<', $startDate)
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            $revenueChange = $previousRevenue > 0 
                ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
                : 0;

            $todayRevenue = Order::whereDate('created_at', Carbon::today())
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            $weekRevenue = Order::where('created_at', '>=', Carbon::now()->subDays(7))
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            // Order metrics
            $currentOrders = Order::where('created_at', '>=', $startDate)->count();
            $previousOrders = Order::where('created_at', '>=', $previousStartDate)
                ->where('created_at', '<', $startDate)
                ->count();

            $ordersChange = $previousOrders > 0 
                ? (($currentOrders - $previousOrders) / $previousOrders) * 100 
                : 0;

            $ordersByStatus = [
                'total' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
                'completed' => Order::where('status', 'completed')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
            ];

            // User metrics
            $currentUsers = User::where('created_at', '>=', $startDate)->count();
            $previousUsers = User::where('created_at', '>=', $previousStartDate)
                ->where('created_at', '<', $startDate)
                ->count();

            $usersChange = $previousUsers > 0 
                ? (($currentUsers - $previousUsers) / $previousUsers) * 100 
                : 0;

            $usersByRole = [
                'total' => User::count(),
                'buyers' => User::where('role', 'buyer')->count(),
                'sellers' => User::where('role', 'seller')->count(),
                'new_this_month' => User::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
            ];

            // Product metrics
            $currentProducts = Product::where('created_at', '>=', $startDate)->count();
            $previousProducts = Product::where('created_at', '>=', $previousStartDate)
                ->where('created_at', '<', $startDate)
                ->count();

            $productsChange = $previousProducts > 0 
                ? (($currentProducts - $previousProducts) / $previousProducts) * 100 
                : 0;

            $productsByStatus = [
                'total' => Product::count(),
                'active' => Product::where('is_active', true)->count(),
                'out_of_stock' => Product::where('stock_quantity', 0)->count(),
            ];

            // Subscription metrics
            $currentSubscriptions = Subscription::where('created_at', '>=', $startDate)
                ->where('status', 'active')
                ->count();
            
            $previousSubscriptions = Subscription::where('created_at', '>=', $previousStartDate)
                ->where('created_at', '<', $startDate)
                ->where('status', 'active')
                ->count();

            $subscriptionsChange = $previousSubscriptions > 0 
                ? (($currentSubscriptions - $previousSubscriptions) / $previousSubscriptions) * 100 
                : 0;

            $subscriptionRevenue = Subscription::where('status', 'active')
                ->where('next_billing_date', '>=', Carbon::now()->startOfMonth())
                ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                ->sum(DB::raw('CASE WHEN subscriptions.billing_cycle = "yearly" THEN subscription_plans.yearly_price ELSE subscription_plans.monthly_price END'));

            // Loyalty metrics
            $loyaltyStats = [
                'total_points_issued' => LoyaltyTransaction::where('type', 'earned')->sum('points'),
                'total_points_redeemed' => LoyaltyTransaction::where('type', 'redeemed')->sum('points'),
                'active_members' => LoyaltyPoint::where('balance', '>', 0)->count(),
            ];

            // Flash sale metrics
            $activeFlashSales = FlashSale::where('is_active', true)
                ->where('start_time', '<=', Carbon::now())
                ->where('end_time', '>=', Carbon::now())
                ->count();

            $flashSaleRevenue = DB::table('flash_sale_products')
                ->join('order_items', 'flash_sale_products.product_id', '=', 'order_items.product_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.created_at', '>=', Carbon::now()->startOfMonth())
                ->where('orders.payment_status', 'paid')
                ->sum('order_items.price');

            $flashSaleProductsSold = DB::table('flash_sale_products')
                ->sum('quantity_sold');

            // Top products
            $topProducts = DB::table('order_items')
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('COUNT(order_items.id) as sales'),
                    DB::raw('SUM(order_items.price * order_items.quantity) as revenue'),
                    'products.images'
                )
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.created_at', '>=', $startDate)
                ->where('orders.payment_status', 'paid')
                ->groupBy('products.id', 'products.name', 'products.images')
                ->orderBy('revenue', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($product) {
                    $images = json_decode($product->images, true);
                    $product->image = $images[0] ?? null;
                    unset($product->images);
                    return $product;
                });

            // Top sellers
            $topSellers = DB::table('products')
                ->select(
                    'users.id',
                    'users.name',
                    DB::raw('COUNT(DISTINCT products.id) as products'),
                    DB::raw('SUM(order_items.price * order_items.quantity) as revenue'),
                    DB::raw('AVG(reviews.rating) as rating')
                )
                ->join('users', 'products.seller_id', '=', 'users.id')
                ->join('order_items', 'products.id', '=', 'order_items.product_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('reviews', 'products.id', '=', 'reviews.product_id')
                ->where('orders.created_at', '>=', $startDate)
                ->where('orders.payment_status', 'paid')
                ->groupBy('users.id', 'users.name')
                ->orderBy('revenue', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($seller) {
                    $seller->rating = $seller->rating ? round($seller->rating, 1) : 0;
                    return $seller;
                });

            // Revenue chart data
            switch ($range) {
                case '7d':
                    $days = 7;
                    break;
                case '30d':
                    $days = 30;
                    break;
                case '90d':
                    $days = 90;
                    break;
                default:
                    $days = 30;
            }

            $revenueChart = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->startOfDay();
                $revenue = Order::whereDate('created_at', $date)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');
                
                $revenueChart[] = [
                    'date' => $date->toDateString(),
                    'revenue' => (float) $revenue
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'revenue' => [
                        'today' => (float) $todayRevenue,
                        'week' => (float) $weekRevenue,
                        'month' => (float) $currentRevenue,
                        'change_percentage' => round($revenueChange, 2)
                    ],
                    'orders' => array_merge($ordersByStatus, [
                        'change_percentage' => round($ordersChange, 2)
                    ]),
                    'users' => array_merge($usersByRole, [
                        'change_percentage' => round($usersChange, 2)
                    ]),
                    'products' => array_merge($productsByStatus, [
                        'change_percentage' => round($productsChange, 2)
                    ]),
                    'subscriptions' => [
                        'active' => Subscription::where('status', 'active')->count(),
                        'revenue_this_month' => (float) $subscriptionRevenue,
                        'change_percentage' => round($subscriptionsChange, 2)
                    ],
                    'loyalty' => $loyaltyStats,
                    'flash_sales' => [
                        'active' => $activeFlashSales,
                        'revenue_this_month' => (float) $flashSaleRevenue,
                        'products_sold' => (int) $flashSaleProductsSold
                    ],
                    'top_products' => $topProducts,
                    'top_sellers' => $topSellers,
                    'revenue_chart' => $revenueChart
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export analytics data as CSV
     */
    public function exportAnalytics(Request $request)
    {
        try {
            $range = $request->get('range', '30d');
            $analyticsData = $this->analytics($request)->getData()->data;

            $csv = "Metric,Value\n";
            $csv .= "Total Revenue,{$analyticsData->revenue->month}\n";
            $csv .= "Today Revenue,{$analyticsData->revenue->today}\n";
            $csv .= "Week Revenue,{$analyticsData->revenue->week}\n";
            $csv .= "Total Orders,{$analyticsData->orders->total}\n";
            $csv .= "Pending Orders,{$analyticsData->orders->pending}\n";
            $csv .= "Completed Orders,{$analyticsData->orders->completed}\n";
            $csv .= "Total Users,{$analyticsData->users->total}\n";
            $csv .= "Total Products,{$analyticsData->products->total}\n";
            $csv .= "Active Subscriptions,{$analyticsData->subscriptions->active}\n";
            $csv .= "Subscription Revenue,{$analyticsData->subscriptions->revenue_this_month}\n";

            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="analytics-' . $range . '-' . date('Y-m-d') . '.csv"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all subscription plans (admin view)
     */
    public function subscriptionPlans()
    {
        try {
            $plans = SubscriptionPlan::orderBy('monthly_price', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $plans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription plans',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new subscription plan
     */
    public function createPlan(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|unique:subscription_plans,slug',
                'description' => 'nullable|string',
                'monthly_price' => 'required|numeric|min:0',
                'yearly_price' => 'nullable|numeric|min:0',
                'features' => 'required|array',
                'max_products' => 'nullable|integer|min:0',
                'max_featured_products' => 'required|integer|min:0',
                'commission_rate' => 'required|numeric|min:0|max:100',
                'is_popular' => 'boolean',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $plan = SubscriptionPlan::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $plan,
                'message' => 'Subscription plan created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update subscription plan
     */
    public function updatePlan($id, Request $request)
    {
        try {
            $plan = SubscriptionPlan::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|unique:subscription_plans,slug,' . $id,
                'description' => 'nullable|string',
                'monthly_price' => 'required|numeric|min:0',
                'yearly_price' => 'nullable|numeric|min:0',
                'features' => 'required|array',
                'max_products' => 'nullable|integer|min:0',
                'max_featured_products' => 'required|integer|min:0',
                'commission_rate' => 'required|numeric|min:0|max:100',
                'is_popular' => 'boolean',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $plan->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $plan,
                'message' => 'Subscription plan updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete subscription plan
     */
    public function deletePlan($id)
    {
        try {
            $plan = SubscriptionPlan::findOrFail($id);

            // Check if plan has active subscriptions
            $activeSubscriptions = Subscription::where('subscription_plan_id', $id)
                ->where('status', 'active')
                ->count();

            if ($activeSubscriptions > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete plan with active subscriptions'
                ], 400);
            }

            $plan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subscription plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
