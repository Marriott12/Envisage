<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticEvent;
use App\Models\UserSession;
use App\Models\BusinessMetric;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    // Main dashboard overview
    public function index(Request $request)
    {
        $period = $request->get('period', '30days');
        $dates = $this->getPeriodDates($period);

        $cacheKey = "dashboard_{$period}_" . auth()->id();

        $data = Cache::remember($cacheKey, 300, function () use ($dates) {
            return [
                'kpis' => $this->getKPIs($dates['start'], $dates['end']),
                'revenue_chart' => $this->getRevenueChart($dates['start'], $dates['end']),
                'top_products' => $this->getTopProducts($dates['start'], $dates['end']),
                'recent_orders' => $this->getRecentOrders(10),
                'traffic_sources' => $this->getTrafficSources($dates['start'], $dates['end']),
                'conversion_funnel' => $this->getConversionFunnel($dates['start'], $dates['end']),
            ];
        });

        return response()->json($data);
    }

    // Get key performance indicators
    protected function getKPIs($startDate, $endDate)
    {
        $previousPeriod = [
            'start' => $startDate->copy()->subDays($startDate->diffInDays($endDate)),
            'end' => $startDate->copy(),
        ];

        $current = [
            'revenue' => Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->sum('total'),
            'orders' => Order::whereBetween('created_at', [$startDate, $endDate])->count(),
            'customers' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'sessions' => UserSession::byDateRange($startDate, $endDate)->count(),
            'avg_order_value' => 0,
            'conversion_rate' => 0,
        ];

        $previous = [
            'revenue' => Order::whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']])
                ->where('status', 'completed')
                ->sum('total'),
            'orders' => Order::whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']])->count(),
            'customers' => User::whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']])->count(),
            'sessions' => UserSession::byDateRange($previousPeriod['start'], $previousPeriod['end'])->count(),
        ];

        $current['avg_order_value'] = $current['orders'] > 0 ? round($current['revenue'] / $current['orders'], 2) : 0;
        $current['conversion_rate'] = $current['sessions'] > 0 ? round(($current['orders'] / $current['sessions']) * 100, 2) : 0;

        return [
            'current' => $current,
            'previous' => $previous,
            'changes' => [
                'revenue' => $this->calculateChange($current['revenue'], $previous['revenue']),
                'orders' => $this->calculateChange($current['orders'], $previous['orders']),
                'customers' => $this->calculateChange($current['customers'], $previous['customers']),
                'conversion_rate' => $current['conversion_rate'] - ($previous['sessions'] > 0 ? round(($previous['orders'] / $previous['sessions']) * 100, 2) : 0),
            ],
        ];
    }

    // Get revenue chart data
    protected function getRevenueChart($startDate, $endDate)
    {
        return Order::selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    // Get top selling products
    protected function getTopProducts($startDate, $endDate, $limit = 5)
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', 'completed')
            ->selectRaw('products.id, products.name, products.image, SUM(order_items.quantity) as units_sold, SUM(order_items.price * order_items.quantity) as revenue')
            ->groupBy('products.id', 'products.name', 'products.image')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    // Get recent orders
    protected function getRecentOrders($limit = 10)
    {
        return Order::with(['user:id,name,email', 'items.product:id,name'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    // Get traffic sources
    protected function getTrafficSources($startDate, $endDate)
    {
        return UserSession::byDateRange($startDate, $endDate)
            ->selectRaw('COALESCE(utm_source, "direct") as source, COUNT(*) as sessions, SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) as conversions')
            ->groupBy('source')
            ->orderByDesc('sessions')
            ->limit(5)
            ->get();
    }

    // Get conversion funnel
    protected function getConversionFunnel($startDate, $endDate)
    {
        $sessions = UserSession::byDateRange($startDate, $endDate)->count();
        $productViews = AnalyticEvent::productViews()->byDateRange($startDate, $endDate)->count();
        $addToCarts = AnalyticEvent::addToCart()->byDateRange($startDate, $endDate)->count();
        $purchases = AnalyticEvent::purchases()->byDateRange($startDate, $endDate)->count();

        return [
            ['step' => 'Sessions', 'count' => $sessions, 'percentage' => 100],
            ['step' => 'Product Views', 'count' => $productViews, 'percentage' => $sessions > 0 ? round(($productViews / $sessions) * 100, 2) : 0],
            ['step' => 'Add to Cart', 'count' => $addToCarts, 'percentage' => $sessions > 0 ? round(($addToCarts / $sessions) * 100, 2) : 0],
            ['step' => 'Purchases', 'count' => $purchases, 'percentage' => $sessions > 0 ? round(($purchases / $sessions) * 100, 2) : 0],
        ];
    }

    // Helper: Calculate percentage change
    protected function calculateChange($current, $previous)
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 2);
    }

    // Helper: Get period dates
    protected function getPeriodDates($period)
    {
        switch ($period) {
            case 'today':
                return ['start' => now()->startOfDay(), 'end' => now()->endOfDay()];
            case '7days':
                return ['start' => now()->subDays(7), 'end' => now()];
            case '30days':
                return ['start' => now()->subDays(30), 'end' => now()];
            case '90days':
                return ['start' => now()->subDays(90), 'end' => now()];
            case 'year':
                return ['start' => now()->subYear(), 'end' => now()];
            default:
                return ['start' => now()->subDays(30), 'end' => now()];
        }
    }
}
