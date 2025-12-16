'use client';

import React, { useState, useEffect } from 'react';
import { 
  ChartBarIcon, 
  ShoppingBagIcon,
  CurrencyDollarIcon,
  UserGroupIcon,
  EyeIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon
} from '@heroicons/react/24/outline';
import api from '@/lib/api';
import { Line, Bar, Doughnut } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

interface AnalyticsData {
  revenue: {
    total: number;
    change: number;
    chart_data: { date: string; amount: number }[];
  };
  orders: {
    total: number;
    change: number;
    pending: number;
    completed: number;
  };
  products: {
    total: number;
    out_of_stock: number;
    top_selling: Array<{
      id: number;
      name: string;
      sales: number;
      revenue: number;
    }>;
  };
  customers: {
    total: number;
    new: number;
    returning: number;
  };
  forecast: {
    next_week: number;
    next_month: number;
    confidence: number;
  };
}

export default function SellerAnalyticsDashboard() {
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [period, setPeriod] = useState<'7days' | '30days' | '90days'>('30days');

  useEffect(() => {
    fetchAnalytics();
  }, [period]);

  const fetchAnalytics = async () => {
    try {
      setLoading(true);
      const response = await api.get('/seller/analytics', {
        params: { period }
      });
      setAnalytics(response.data);
    } catch (error) {
      console.error('Failed to fetch analytics:', error);
    } finally {
      setLoading(false);
    }
  };

  const revenueChartData = {
    labels: analytics?.revenue.chart_data.map(d => d.date) || [],
    datasets: [
      {
        label: 'Revenue',
        data: analytics?.revenue.chart_data.map(d => d.amount) || [],
        borderColor: 'rgb(99, 102, 241)',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        fill: true,
        tension: 0.4,
      },
    ],
  };

  const ordersChartData = {
    labels: ['Pending', 'Processing', 'Completed', 'Cancelled'],
    datasets: [
      {
        data: [
          analytics?.orders.pending || 0,
          20,
          analytics?.orders.completed || 0,
          5
        ],
        backgroundColor: [
          'rgba(251, 191, 36, 0.8)',
          'rgba(59, 130, 246, 0.8)',
          'rgba(34, 197, 94, 0.8)',
          'rgba(239, 68, 68, 0.8)',
        ],
        borderColor: [
          'rgb(251, 191, 36)',
          'rgb(59, 130, 246)',
          'rgb(34, 197, 94)',
          'rgb(239, 68, 68)',
        ],
        borderWidth: 1,
      },
    ],
  };

  const StatCard = ({ 
    title, 
    value, 
    change, 
    icon: Icon, 
    color 
  }: { 
    title: string; 
    value: string | number; 
    change?: number; 
    icon: any; 
    color: string;
  }) => (
    <div className="bg-white rounded-lg shadow p-6">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-600">{title}</p>
          <p className="text-2xl font-bold text-gray-900 mt-2">{value}</p>
          {change !== undefined && (
            <div className={`flex items-center gap-1 mt-2 text-sm ${
              change >= 0 ? 'text-green-600' : 'text-red-600'
            }`}>
              {change >= 0 ? (
                <ArrowTrendingUpIcon className="w-4 h-4" />
              ) : (
                <ArrowTrendingDownIcon className="w-4 h-4" />
              )}
              <span>{Math.abs(change)}%</span>
            </div>
          )}
        </div>
        <div className={`w-12 h-12 rounded-full ${color} flex items-center justify-center`}>
          <Icon className="w-6 h-6 text-white" />
        </div>
      </div>
    </div>
  );

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 p-6">
        <div className="max-w-7xl mx-auto">
          <div className="animate-pulse space-y-6">
            <div className="h-8 bg-gray-200 rounded w-64"></div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              {[...Array(4)].map((_, i) => (
                <div key={i} className="h-32 bg-gray-200 rounded-lg"></div>
              ))}
            </div>
            <div className="h-96 bg-gray-200 rounded-lg"></div>
          </div>
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
            <h1 className="text-3xl font-bold text-gray-900">Seller Analytics</h1>
            <p className="text-gray-600 mt-1">Track your store performance and insights</p>
          </div>
          <div className="flex gap-2">
            {(['7days', '30days', '90days'] as const).map((p) => (
              <button
                key={p}
                onClick={() => setPeriod(p)}
                className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                  period === p
                    ? 'bg-primary-600 text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-100'
                }`}
              >
                {p === '7days' ? '7 Days' : p === '30days' ? '30 Days' : '90 Days'}
              </button>
            ))}
          </div>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <StatCard
            title="Total Revenue"
            value={`$${analytics?.revenue.total.toLocaleString() || 0}`}
            change={analytics?.revenue.change}
            icon={CurrencyDollarIcon}
            color="bg-green-500"
          />
          <StatCard
            title="Total Orders"
            value={analytics?.orders.total || 0}
            change={analytics?.orders.change}
            icon={ShoppingBagIcon}
            color="bg-blue-500"
          />
          <StatCard
            title="Total Customers"
            value={analytics?.customers.total || 0}
            icon={UserGroupIcon}
            color="bg-purple-500"
          />
          <StatCard
            title="Products Listed"
            value={analytics?.products.total || 0}
            icon={ChartBarIcon}
            color="bg-orange-500"
          />
        </div>

        {/* Charts Row */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          {/* Revenue Chart */}
          <div className="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Revenue Trend</h3>
            <div className="h-80">
              <Line 
                data={revenueChartData} 
                options={{
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: {
                    legend: { display: false },
                  },
                  scales: {
                    y: {
                      beginAtZero: true,
                      ticks: {
                        callback: (value) => `$${value}`
                      }
                    }
                  }
                }}
              />
            </div>
          </div>

          {/* Orders Distribution */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Order Status</h3>
            <div className="h-80 flex items-center justify-center">
              <Doughnut 
                data={ordersChartData}
                options={{
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: {
                    legend: {
                      position: 'bottom',
                    },
                  },
                }}
              />
            </div>
          </div>
        </div>

        {/* Top Products & Forecast */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Top Selling Products */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Top Selling Products</h3>
            <div className="space-y-4">
              {analytics?.products.top_selling.slice(0, 5).map((product, index) => (
                <div key={product.id} className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                      <span className="text-sm font-bold text-primary-600">#{index + 1}</span>
                    </div>
                    <div>
                      <p className="font-medium text-gray-900">{product.name}</p>
                      <p className="text-sm text-gray-500">{product.sales} sales</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="font-semibold text-gray-900">
                      ${product.revenue.toLocaleString()}
                    </p>
                  </div>
                </div>
              )) || (
                <p className="text-center text-gray-500 py-8">No sales data yet</p>
              )}
            </div>
          </div>

          {/* Revenue Forecast */}
          <div className="bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg shadow p-6 text-white">
            <h3 className="text-lg font-semibold mb-4 flex items-center gap-2">
              <ArrowTrendingUpIcon className="w-5 h-5" />
              Revenue Forecast
            </h3>
            <div className="space-y-6">
              <div>
                <p className="text-primary-100 text-sm mb-2">Next 7 Days</p>
                <p className="text-3xl font-bold">
                  ${analytics?.forecast.next_week.toLocaleString() || 0}
                </p>
              </div>
              <div>
                <p className="text-primary-100 text-sm mb-2">Next 30 Days</p>
                <p className="text-3xl font-bold">
                  ${analytics?.forecast.next_month.toLocaleString() || 0}
                </p>
              </div>
              <div className="pt-4 border-t border-primary-400">
                <p className="text-primary-100 text-sm">Forecast Confidence</p>
                <div className="flex items-center gap-2 mt-2">
                  <div className="flex-1 bg-primary-400 rounded-full h-2">
                    <div 
                      className="bg-white rounded-full h-2"
                      style={{ width: `${analytics?.forecast.confidence || 0}%` }}
                    ></div>
                  </div>
                  <span className="text-sm font-semibold">
                    {analytics?.forecast.confidence || 0}%
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
