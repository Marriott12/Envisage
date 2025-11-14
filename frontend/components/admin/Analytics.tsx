import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { toast } from 'react-hot-toast';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://envisagezm.com/api';

interface AnalyticsData {
  users?: {
    total: number;
    customers: number;
    sellers: number;
    admins: number;
  };
  products?: {
    total: number;
    active: number;
    draft: number;
    out_of_stock: number;
  };
  orders?: {
    total: number;
    pending: number;
    processing: number;
    delivered: number;
    cancelled: number;
  };
  revenue?: {
    total: number;
    this_month: number;
    last_month: number;
  };
}

export default function Analytics() {
  const [data, setData] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchAnalytics();
  }, []);

  const fetchAnalytics = async () => {
    try {
      const token = localStorage.getItem('envisage_auth_token');
      const response = await axios.get(`${API_URL}/admin/statistics`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      setData(response.data);
    } catch (error: any) {
      console.error('Failed to load analytics:', error);
      toast.error('Failed to load analytics data');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-12">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  // Calculate metrics
  const revenueThisMonth = data?.revenue?.this_month || 0;
  const revenueLastMonth = data?.revenue?.last_month || 0;
  const revenueChange = revenueLastMonth > 0 
    ? ((revenueThisMonth - revenueLastMonth) / revenueLastMonth * 100).toFixed(1)
    : '0';
  const revenueDirection = parseFloat(revenueChange) >= 0 ? '↑' : '↓';

  const newCustomers = data?.users?.customers || 0;
  const ordersThisMonth = data?.orders?.total || 0;
  const avgOrderValue = ordersThisMonth > 0 ? (revenueThisMonth / ordersThisMonth) : 0;

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold text-gray-900">Analytics & Reports</h2>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div className="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow p-6 text-white">
          <div className="text-sm opacity-90 mb-1">Revenue This Month</div>
          <div className="text-3xl font-bold">${revenueThisMonth.toFixed(2)}</div>
          <div className="text-sm mt-2 opacity-75">
            {revenueDirection} {Math.abs(parseFloat(revenueChange))}% from last month
          </div>
        </div>
        <div className="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow p-6 text-white">
          <div className="text-sm opacity-90 mb-1">Total Customers</div>
          <div className="text-3xl font-bold">{newCustomers}</div>
          <div className="text-sm mt-2 opacity-75">Registered users</div>
        </div>
        <div className="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow p-6 text-white">
          <div className="text-sm opacity-90 mb-1">Total Orders</div>
          <div className="text-3xl font-bold">{ordersThisMonth}</div>
          <div className="text-sm mt-2 opacity-75">
            {data?.orders?.delivered || 0} delivered
          </div>
        </div>
        <div className="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow p-6 text-white">
          <div className="text-sm opacity-90 mb-1">Avg Order Value</div>
          <div className="text-3xl font-bold">${avgOrderValue.toFixed(2)}</div>
          <div className="text-sm mt-2 opacity-75">Per transaction</div>
        </div>
      </div>

      {/* Detailed Metrics */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Revenue Breakdown */}
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Revenue Breakdown</h3>
          <div className="space-y-4">
            <div>
              <div className="flex justify-between items-center mb-2">
                <span className="text-sm text-gray-600">Total Revenue</span>
                <span className="font-bold text-gray-900">${(data?.revenue?.total || 0).toFixed(2)}</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div className="bg-green-600 h-2 rounded-full" style={{ width: '100%' }}></div>
              </div>
            </div>
            <div>
              <div className="flex justify-between items-center mb-2">
                <span className="text-sm text-gray-600">This Month</span>
                <span className="font-bold text-blue-600">${revenueThisMonth.toFixed(2)}</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div 
                  className="bg-blue-600 h-2 rounded-full" 
                  style={{ 
                    width: `${data?.revenue?.total ? (revenueThisMonth / data.revenue.total * 100) : 0}%` 
                  }}
                ></div>
              </div>
            </div>
            <div>
              <div className="flex justify-between items-center mb-2">
                <span className="text-sm text-gray-600">Last Month</span>
                <span className="font-bold text-purple-600">${revenueLastMonth.toFixed(2)}</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div 
                  className="bg-purple-600 h-2 rounded-full" 
                  style={{ 
                    width: `${data?.revenue?.total ? (revenueLastMonth / data.revenue.total * 100) : 0}%` 
                  }}
                ></div>
              </div>
            </div>
          </div>
        </div>

        {/* Order Status */}
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Order Status</h3>
          <div className="space-y-3">
            <div className="flex justify-between items-center">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 bg-yellow-500 rounded-full"></div>
                <span className="text-sm text-gray-600">Pending</span>
              </div>
              <span className="font-semibold text-gray-900">{data?.orders?.pending || 0}</span>
            </div>
            <div className="flex justify-between items-center">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 bg-blue-500 rounded-full"></div>
                <span className="text-sm text-gray-600">Processing</span>
              </div>
              <span className="font-semibold text-gray-900">{data?.orders?.processing || 0}</span>
            </div>
            <div className="flex justify-between items-center">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                <span className="text-sm text-gray-600">Delivered</span>
              </div>
              <span className="font-semibold text-gray-900">{data?.orders?.delivered || 0}</span>
            </div>
            <div className="flex justify-between items-center">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 bg-red-500 rounded-full"></div>
                <span className="text-sm text-gray-600">Cancelled</span>
              </div>
              <span className="font-semibold text-gray-900">{data?.orders?.cancelled || 0}</span>
            </div>
            <div className="pt-3 border-t">
              <div className="flex justify-between items-center">
                <span className="font-medium text-gray-900">Total Orders</span>
                <span className="font-bold text-primary-600">{data?.orders?.total || 0}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Product Status */}
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Product Inventory</h3>
          <div className="space-y-3">
            <div className="flex justify-between items-center">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                <span className="text-sm text-gray-600">Active</span>
              </div>
              <span className="font-semibold text-gray-900">{data?.products?.active || 0}</span>
            </div>
            <div className="flex justify-between items-center">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 bg-yellow-500 rounded-full"></div>
                <span className="text-sm text-gray-600">Draft</span>
              </div>
              <span className="font-semibold text-gray-900">{data?.products?.draft || 0}</span>
            </div>
            <div className="flex justify-between items-center">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 bg-red-500 rounded-full"></div>
                <span className="text-sm text-gray-600">Out of Stock</span>
              </div>
              <span className="font-semibold text-gray-900">{data?.products?.out_of_stock || 0}</span>
            </div>
            <div className="pt-3 border-t">
              <div className="flex justify-between items-center">
                <span className="font-medium text-gray-900">Total Products</span>
                <span className="font-bold text-primary-600">{data?.products?.total || 0}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Charts Placeholder */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Sales Overview</h3>
          <div className="h-64 flex items-center justify-center bg-gray-50 rounded">
            <div className="text-gray-400 text-center">
              <svg className="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <p>Chart visualization coming soon</p>
            </div>
          </div>
        </div>
        
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">User Growth</h3>
          <div className="h-64 flex items-center justify-center bg-gray-50 rounded">
            <div className="text-gray-400 text-center">
              <svg className="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
              <p>Chart visualization coming soon</p>
            </div>
          </div>
        </div>
      </div>

      {/* User Statistics */}
      <div className="bg-white rounded-lg shadow p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">User Distribution</h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="text-center p-4 bg-blue-50 rounded-lg">
            <div className="text-3xl font-bold text-blue-600">{data?.users?.customers || 0}</div>
            <div className="text-sm text-gray-600 mt-1">Customers</div>
            <div className="text-xs text-gray-500 mt-1">
              {data?.users?.total ? ((data.users.customers / data.users.total * 100).toFixed(1)) : 0}% of total
            </div>
          </div>
          <div className="text-center p-4 bg-purple-50 rounded-lg">
            <div className="text-3xl font-bold text-purple-600">{data?.users?.sellers || 0}</div>
            <div className="text-sm text-gray-600 mt-1">Sellers</div>
            <div className="text-xs text-gray-500 mt-1">
              {data?.users?.total ? ((data.users.sellers / data.users.total * 100).toFixed(1)) : 0}% of total
            </div>
          </div>
          <div className="text-center p-4 bg-green-50 rounded-lg">
            <div className="text-3xl font-bold text-green-600">{data?.users?.admins || 0}</div>
            <div className="text-sm text-gray-600 mt-1">Admins</div>
            <div className="text-xs text-gray-500 mt-1">
              {data?.users?.total ? ((data.users.admins / data.users.total * 100).toFixed(1)) : 0}% of total
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
