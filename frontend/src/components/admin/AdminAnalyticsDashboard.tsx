import React, { useState, useEffect } from 'react';
import {
  TrendingUp,
  DollarSign,
  ShoppingCart,
  Users,
  Package,
  Star,
  Zap,
  Award,
  ArrowUp,
  ArrowDown,
  Calendar,
  Download,
} from 'lucide-react';

interface AnalyticsData {
  revenue: {
    today: number;
    week: number;
    month: number;
    change_percentage: number;
  };
  orders: {
    total: number;
    pending: number;
    completed: number;
    cancelled: number;
    change_percentage: number;
  };
  users: {
    total: number;
    buyers: number;
    sellers: number;
    new_this_month: number;
    change_percentage: number;
  };
  products: {
    total: number;
    active: number;
    out_of_stock: number;
    change_percentage: number;
  };
  subscriptions: {
    active: number;
    revenue_this_month: number;
    change_percentage: number;
  };
  loyalty: {
    total_points_issued: number;
    total_points_redeemed: number;
    active_members: number;
  };
  flash_sales: {
    active: number;
    revenue_this_month: number;
    products_sold: number;
  };
  top_products: Array<{
    id: number;
    name: string;
    sales: number;
    revenue: number;
    image: string;
  }>;
  top_sellers: Array<{
    id: number;
    name: string;
    products: number;
    revenue: number;
    rating: number;
  }>;
  revenue_chart: Array<{
    date: string;
    revenue: number;
  }>;
}

interface AdminAnalyticsDashboardProps {
  apiToken: string;
}

export default function AdminAnalyticsDashboard({ apiToken }: AdminAnalyticsDashboardProps) {
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [timeRange, setTimeRange] = useState<'7d' | '30d' | '90d'>('30d');

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  useEffect(() => {
    fetchAnalytics();
  }, [timeRange]);

  const fetchAnalytics = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${API_BASE}/admin/analytics?range=${timeRange}`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success) {
        setAnalytics(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch analytics:', error);
    } finally {
      setLoading(false);
    }
  };

  const exportReport = async () => {
    try {
      const response = await fetch(`${API_BASE}/admin/analytics/export?range=${timeRange}`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `analytics-${timeRange}-${new Date().toISOString().split('T')[0]}.csv`;
      a.click();
    } catch (error) {
      console.error('Failed to export report:', error);
      alert('Failed to export report');
    }
  };

  const getChangeIndicator = (percentage: number) => {
    if (percentage > 0) {
      return (
        <span className="flex items-center gap-1 text-green-600 text-sm font-medium">
          <ArrowUp size={16} />
          {percentage.toFixed(1)}%
        </span>
      );
    } else if (percentage < 0) {
      return (
        <span className="flex items-center gap-1 text-red-600 text-sm font-medium">
          <ArrowDown size={16} />
          {Math.abs(percentage).toFixed(1)}%
        </span>
      );
    }
    return <span className="text-gray-500 text-sm">No change</span>;
  };

  if (loading || !analytics) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading analytics...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Analytics Dashboard</h1>
            <p className="text-gray-600">Monitor your marketplace performance</p>
          </div>
          <div className="flex gap-3">
            <select
              value={timeRange}
              onChange={(e) => setTimeRange(e.target.value as any)}
              className="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
            >
              <option value="7d">Last 7 days</option>
              <option value="30d">Last 30 days</option>
              <option value="90d">Last 90 days</option>
            </select>
            <button
              onClick={exportReport}
              className="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 flex items-center gap-2"
            >
              <Download size={20} />
              Export Report
            </button>
          </div>
        </div>

        {/* Key Metrics */}
        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between mb-4">
              <div className="bg-green-100 p-3 rounded-lg">
                <DollarSign className="text-green-600" size={24} />
              </div>
              {getChangeIndicator(analytics.revenue.change_percentage)}
            </div>
            <p className="text-gray-600 text-sm mb-1">Total Revenue</p>
            <p className="text-3xl font-bold text-gray-900">${analytics.revenue.month.toLocaleString()}</p>
            <p className="text-xs text-gray-500 mt-2">
              Today: ${analytics.revenue.today.toLocaleString()} | Week: ${analytics.revenue.week.toLocaleString()}
            </p>
          </div>

          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between mb-4">
              <div className="bg-blue-100 p-3 rounded-lg">
                <ShoppingCart className="text-blue-600" size={24} />
              </div>
              {getChangeIndicator(analytics.orders.change_percentage)}
            </div>
            <p className="text-gray-600 text-sm mb-1">Total Orders</p>
            <p className="text-3xl font-bold text-gray-900">{analytics.orders.total.toLocaleString()}</p>
            <p className="text-xs text-gray-500 mt-2">
              Pending: {analytics.orders.pending} | Completed: {analytics.orders.completed}
            </p>
          </div>

          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between mb-4">
              <div className="bg-purple-100 p-3 rounded-lg">
                <Users className="text-purple-600" size={24} />
              </div>
              {getChangeIndicator(analytics.users.change_percentage)}
            </div>
            <p className="text-gray-600 text-sm mb-1">Total Users</p>
            <p className="text-3xl font-bold text-gray-900">{analytics.users.total.toLocaleString()}</p>
            <p className="text-xs text-gray-500 mt-2">
              Buyers: {analytics.users.buyers} | Sellers: {analytics.users.sellers}
            </p>
          </div>

          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center justify-between mb-4">
              <div className="bg-orange-100 p-3 rounded-lg">
                <Package className="text-orange-600" size={24} />
              </div>
              {getChangeIndicator(analytics.products.change_percentage)}
            </div>
            <p className="text-gray-600 text-sm mb-1">Total Products</p>
            <p className="text-3xl font-bold text-gray-900">{analytics.products.total.toLocaleString()}</p>
            <p className="text-xs text-gray-500 mt-2">
              Active: {analytics.products.active} | Out of Stock: {analytics.products.out_of_stock}
            </p>
          </div>
        </div>

        {/* Revenue Chart */}
        <div className="bg-white rounded-lg shadow-sm border p-6 mb-8">
          <h2 className="text-xl font-bold mb-6">Revenue Trend</h2>
          <div className="h-64 flex items-end justify-between gap-2">
            {analytics.revenue_chart.map((item, index) => {
              const maxRevenue = Math.max(...analytics.revenue_chart.map(i => i.revenue));
              const height = (item.revenue / maxRevenue) * 100;
              return (
                <div key={index} className="flex-1 flex flex-col items-center">
                  <div className="relative group w-full">
                    <div
                      className="w-full bg-gradient-to-t from-purple-600 to-purple-400 rounded-t hover:opacity-80 transition cursor-pointer"
                      style={{ height: `${height}%` }}
                    ></div>
                    <div className="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white px-2 py-1 rounded text-xs whitespace-nowrap opacity-0 group-hover:opacity-100 transition">
                      ${item.revenue.toLocaleString()}
                    </div>
                  </div>
                  <p className="text-xs text-gray-500 mt-2">
                    {new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                  </p>
                </div>
              );
            })}
          </div>
        </div>

        {/* Additional Metrics */}
        <div className="grid md:grid-cols-3 gap-6 mb-8">
          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center gap-3 mb-4">
              <div className="bg-purple-100 p-3 rounded-lg">
                <Star className="text-purple-600" size={24} />
              </div>
              <div>
                <p className="text-gray-600 text-sm">Subscriptions</p>
                <p className="text-2xl font-bold">{analytics.subscriptions.active}</p>
              </div>
            </div>
            <div className="border-t pt-3">
              <p className="text-sm text-gray-600">Monthly Revenue</p>
              <p className="text-lg font-semibold text-green-600">
                ${analytics.subscriptions.revenue_this_month.toLocaleString()}
              </p>
              <div className="mt-2">{getChangeIndicator(analytics.subscriptions.change_percentage)}</div>
            </div>
          </div>

          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center gap-3 mb-4">
              <div className="bg-yellow-100 p-3 rounded-lg">
                <Award className="text-yellow-600" size={24} />
              </div>
              <div>
                <p className="text-gray-600 text-sm">Loyalty Program</p>
                <p className="text-2xl font-bold">{analytics.loyalty.active_members}</p>
              </div>
            </div>
            <div className="border-t pt-3 space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-gray-600">Points Issued:</span>
                <span className="font-semibold">{analytics.loyalty.total_points_issued.toLocaleString()}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-600">Points Redeemed:</span>
                <span className="font-semibold">{analytics.loyalty.total_points_redeemed.toLocaleString()}</span>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg p-6 shadow-sm border">
            <div className="flex items-center gap-3 mb-4">
              <div className="bg-red-100 p-3 rounded-lg">
                <Zap className="text-red-600" size={24} />
              </div>
              <div>
                <p className="text-gray-600 text-sm">Flash Sales</p>
                <p className="text-2xl font-bold">{analytics.flash_sales.active}</p>
              </div>
            </div>
            <div className="border-t pt-3 space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-gray-600">Monthly Revenue:</span>
                <span className="font-semibold text-green-600">
                  ${analytics.flash_sales.revenue_this_month.toLocaleString()}
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-600">Products Sold:</span>
                <span className="font-semibold">{analytics.flash_sales.products_sold.toLocaleString()}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Top Products & Sellers */}
        <div className="grid lg:grid-cols-2 gap-6">
          <div className="bg-white rounded-lg shadow-sm border">
            <div className="p-6 border-b">
              <h2 className="text-xl font-bold">Top Products</h2>
            </div>
            <div className="divide-y">
              {analytics.top_products.map((product, index) => (
                <div key={product.id} className="p-4 hover:bg-gray-50">
                  <div className="flex items-center gap-4">
                    <div className="bg-purple-100 text-purple-600 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">
                      {index + 1}
                    </div>
                    {product.image && (
                      <img
                        src={product.image}
                        alt={product.name}
                        className="w-12 h-12 object-cover rounded"
                      />
                    )}
                    <div className="flex-1">
                      <p className="font-semibold text-gray-900">{product.name}</p>
                      <p className="text-sm text-gray-600">{product.sales} sales</p>
                    </div>
                    <div className="text-right">
                      <p className="font-bold text-green-600">${product.revenue.toLocaleString()}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm border">
            <div className="p-6 border-b">
              <h2 className="text-xl font-bold">Top Sellers</h2>
            </div>
            <div className="divide-y">
              {analytics.top_sellers.map((seller, index) => (
                <div key={seller.id} className="p-4 hover:bg-gray-50">
                  <div className="flex items-center gap-4">
                    <div className="bg-blue-100 text-blue-600 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">
                      {index + 1}
                    </div>
                    <div className="flex-1">
                      <p className="font-semibold text-gray-900">{seller.name}</p>
                      <div className="flex items-center gap-3 text-sm text-gray-600">
                        <span>{seller.products} products</span>
                        <span className="flex items-center gap-1">
                          <Star size={14} className="text-yellow-500 fill-yellow-500" />
                          {seller.rating.toFixed(1)}
                        </span>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="font-bold text-green-600">${seller.revenue.toLocaleString()}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
