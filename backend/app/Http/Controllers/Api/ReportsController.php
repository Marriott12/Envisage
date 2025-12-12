<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\ProductAnalytic;
use App\Models\CohortAnalysis;
use App\Models\UserBehaviorScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    // Sales report
    public function sales(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());
        $groupBy = $request->get('group_by', 'day'); // day, week, month

        switch ($groupBy) {
            case 'week':
                $dateFormat = '%Y-%u';
                break;
            case 'month':
                $dateFormat = '%Y-%m';
                break;
            default:
                $dateFormat = '%Y-%m-%d';
                break;
        }

        $report = Order::selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period")
            ->selectRaw('COUNT(*) as orders, SUM(total) as revenue, AVG(total) as avg_order_value')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json($report);
    }

    // Product performance report
    public function productPerformance(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());
        $limit = $request->get('limit', 20);

        $products = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', 'completed')
            ->selectRaw('products.id, products.name, products.price')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('SUM(order_items.price * order_items.quantity) as revenue')
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->groupBy('products.id', 'products.name', 'products.price')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        return response()->json($products);
    }

    // Customer report
    public function customers(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        $report = [
            'new_customers' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_customers' => User::count(),
            'customers_with_orders' => User::has('orders')->count(),
            'top_customers' => $this->getTopCustomers($startDate, $endDate),
            'customer_lifetime_value' => $this->getCustomerLTV(),
        ];

        return response()->json($report);
    }

    // Get top customers
    protected function getTopCustomers($startDate, $endDate, $limit = 10)
    {
        return DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', 'completed')
            ->selectRaw('users.id, users.name, users.email')
            ->selectRaw('COUNT(orders.id) as order_count')
            ->selectRaw('SUM(orders.total) as total_spent')
            ->selectRaw('AVG(orders.total) as avg_order_value')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();
    }

    // Get customer lifetime value
    protected function getCustomerLTV()
    {
        return DB::table('orders')
            ->where('status', 'completed')
            ->selectRaw('AVG(total_spent) as avg_ltv')
            ->from(DB::raw('(SELECT user_id, SUM(total) as total_spent FROM orders WHERE status = "completed" GROUP BY user_id) as customer_totals'))
            ->value('avg_ltv') ?? 0;
    }

    // Cohort analysis report
    public function cohortAnalysis(Request $request)
    {
        $cohortType = $request->get('cohort_type', 'registration');
        $periodType = $request->get('period_type', 'week');
        $startDate = $request->get('start_date', now()->subMonths(6));
        $endDate = $request->get('end_date', now());

        $cohorts = CohortAnalysis::where('cohort_type', $cohortType)
            ->where('period_type', $periodType)
            ->whereBetween('cohort_date', [$startDate, $endDate])
            ->orderBy('cohort_date')
            ->orderBy('period_number')
            ->get()
            ->groupBy('cohort_date');

        return response()->json($cohorts);
    }

    // User behavior segments
    public function userSegments()
    {
        $segments = UserBehaviorScore::selectRaw('user_segment, COUNT(*) as count')
            ->selectRaw('AVG(engagement_score) as avg_engagement')
            ->selectRaw('AVG(purchase_propensity) as avg_purchase_propensity')
            ->selectRaw('AVG(lifetime_value_prediction) as avg_ltv')
            ->groupBy('user_segment')
            ->get();

        return response()->json($segments);
    }

    // Export report (CSV)
    public function export(Request $request)
    {
        $reportType = $request->get('type', 'sales');
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        switch ($reportType) {
            case 'sales':
                $data = $this->sales($request)->getData();
                break;
            case 'products':
                $data = $this->productPerformance($request)->getData();
                break;
            case 'customers':
                $data = $this->customers($request)->getData();
                break;
            default:
                $data = [];
                break;
        }

        // Convert to CSV
        $filename = "{$reportType}_report_" . now()->format('Y-m-d') . ".csv";
        
        return response()->json([
            'message' => 'Report generated',
            'filename' => $filename,
            'data' => $data,
        ]);
    }
}
