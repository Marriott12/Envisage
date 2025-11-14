'use client';

import React, { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/hooks/useAuth';
import axios from 'axios';
import Header from '@/components/Header';
import { toast } from 'react-hot-toast';
import dynamic from 'next/dynamic';

const UsersManagement = dynamic(() => import('@/components/admin/UsersManagement'), { ssr: false });
const ProductsManagement = dynamic(() => import('@/components/admin/ProductsManagement'), { ssr: false });
const OrdersManagement = dynamic(() => import('@/components/admin/OrdersManagement'), { ssr: false });
const CategoriesManagement = dynamic(() => import('@/components/admin/CategoriesManagement'), { ssr: false });
const SettingsManagement = dynamic(() => import('@/components/admin/SettingsManagement'), { ssr: false });
const SystemLogs = dynamic(() => import('@/components/admin/SystemLogs'), { ssr: false });
const Analytics = dynamic(() => import('@/components/admin/Analytics'), { ssr: false });

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://envisagezm.com/api';

interface AdminOverview {
  total_users?: number;
  total_products?: number;
  total_orders?: number;
  total_revenue?: number;
  pending_orders?: number;
  active_products?: number;
  recent_orders?: any[];
  recent_users?: any[];
}

interface AdminStatistics {
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

type TabType = 'overview' | 'users' | 'products' | 'orders' | 'categories' | 'settings' | 'logs' | 'analytics';

export default function AdminPanel() {
  const { user, isAuthenticated, isLoading } = useAuth();
  const router = useRouter();
  const [data, setData] = useState<AdminOverview | null>(null);
  const [statistics, setStatistics] = useState<AdminStatistics | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<TabType>('overview');

  const fetchAdminData = async () => {
    try {
      const token = localStorage.getItem('envisage_auth_token');
      
      // Fetch overview data
      const overviewResponse = await axios.get(`${API_URL}/admin/overview`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      });
      setData(overviewResponse.data);
      
      // Fetch statistics data
      const statsResponse = await axios.get(`${API_URL}/admin/statistics`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      });
      setStatistics(statsResponse.data);
    } catch (error: any) {
      console.error('Failed to fetch admin data:', error);
      toast.error(error.response?.data?.message || 'Failed to load admin data');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    // Check authentication and admin role
    if (!isLoading) {
      if (!isAuthenticated) {
        router.push('/login?redirect=/admin-panel');
        return;
      }
      
      const userRole = (user as any)?.role;
      if (userRole !== 'admin') {
        toast.error('Access denied. Admin privileges required.');
        router.push('/dashboard');
        return;
      }

      // Fetch admin data
      fetchAdminData();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isLoading, isAuthenticated, user, router]);

  if (isLoading || loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  const tabs = [
    { id: 'overview' as TabType, label: 'Overview', icon: '📊' },
    { id: 'users' as TabType, label: 'Users', icon: '👥' },
    { id: 'products' as TabType, label: 'Products', icon: '📦' },
    { id: 'orders' as TabType, label: 'Orders', icon: '🛒' },
    { id: 'categories' as TabType, label: 'Categories', icon: '🏷️' },
    { id: 'analytics' as TabType, label: 'Analytics', icon: '📈' },
    { id: 'settings' as TabType, label: 'Settings', icon: '⚙️' },
    { id: 'logs' as TabType, label: 'Logs', icon: '📝' },
  ];

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <div className="bg-white shadow">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="py-6">
              <h1 className="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
              <p className="mt-1 text-sm text-gray-600">Comprehensive system management</p>
            </div>
          </div>
        </div>

        {/* Tabs Navigation */}
        <div className="bg-white border-b border-gray-200">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav className="flex space-x-8 overflow-x-auto" aria-label="Tabs">
              {tabs.map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`
                    whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2
                    ${activeTab === tab.id
                      ? 'border-primary-500 text-primary-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    }
                  `}
                >
                  <span>{tab.icon}</span>
                  {tab.label}
                </button>
              ))}
            </nav>
          </div>
        </div>

        {/* Content */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {activeTab === 'overview' && (
            <div className="space-y-6">
              {/* Stats Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatCard
                  title="Total Users"
                  value={data?.total_users || 0}
                  icon="👥"
                  color="blue"
                />
                <StatCard
                  title="Total Products"
                  value={data?.total_products || 0}
                  icon="📦"
                  color="green"
                />
                <StatCard
                  title="Total Orders"
                  value={data?.total_orders || 0}
                  icon="🛒"
                  color="purple"
                />
                <StatCard
                  title="Total Revenue"
                  value={`$${(data?.total_revenue || 0).toFixed(2)}`}
                  icon="💰"
                  color="yellow"
                />
              </div>

              {/* Additional Overview Stats */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-lg shadow p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm text-gray-600">Pending Orders</p>
                      <p className="text-3xl font-bold text-orange-600 mt-2">
                        {data?.pending_orders || 0}
                      </p>
                    </div>
                    <div className="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                      <span className="text-2xl">⏳</span>
                    </div>
                  </div>
                  <p className="text-xs text-gray-500 mt-3">Requires attention</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm text-gray-600">Active Products</p>
                      <p className="text-3xl font-bold text-green-600 mt-2">
                        {data?.active_products || 0}
                      </p>
                    </div>
                    <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                      <span className="text-2xl">✅</span>
                    </div>
                  </div>
                  <p className="text-xs text-gray-500 mt-3">Currently listed</p>
                </div>
              </div>

              {/* Detailed Statistics */}
              {statistics && (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  {/* Users Breakdown */}
                  <div className="bg-white rounded-lg shadow p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Users Breakdown</h3>
                    <div className="space-y-3">
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Customers</span>
                        <span className="font-semibold text-gray-900">{statistics.users?.customers || 0}</span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Sellers</span>
                        <span className="font-semibold text-gray-900">{statistics.users?.sellers || 0}</span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Admins</span>
                        <span className="font-semibold text-gray-900">{statistics.users?.admins || 0}</span>
                      </div>
                      <div className="pt-3 border-t">
                        <div className="flex justify-between items-center">
                          <span className="font-medium text-gray-900">Total</span>
                          <span className="font-bold text-primary-600">{statistics.users?.total || 0}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Products Breakdown */}
                  <div className="bg-white rounded-lg shadow p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Products Status</h3>
                    <div className="space-y-3">
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Active</span>
                        <span className="font-semibold text-green-600">{statistics.products?.active || 0}</span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Draft</span>
                        <span className="font-semibold text-yellow-600">{statistics.products?.draft || 0}</span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Out of Stock</span>
                        <span className="font-semibold text-red-600">{statistics.products?.out_of_stock || 0}</span>
                      </div>
                      <div className="pt-3 border-t">
                        <div className="flex justify-between items-center">
                          <span className="font-medium text-gray-900">Total</span>
                          <span className="font-bold text-primary-600">{statistics.products?.total || 0}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Orders Breakdown */}
                  <div className="bg-white rounded-lg shadow p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Orders Status</h3>
                    <div className="space-y-3">
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Pending</span>
                        <span className="font-semibold text-yellow-600">{statistics.orders?.pending || 0}</span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Processing</span>
                        <span className="font-semibold text-blue-600">{statistics.orders?.processing || 0}</span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Delivered</span>
                        <span className="font-semibold text-green-600">{statistics.orders?.delivered || 0}</span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600">Cancelled</span>
                        <span className="font-semibold text-red-600">{statistics.orders?.cancelled || 0}</span>
                      </div>
                      <div className="pt-3 border-t">
                        <div className="flex justify-between items-center">
                          <span className="font-medium text-gray-900">Total</span>
                          <span className="font-bold text-primary-600">{statistics.orders?.total || 0}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* Revenue Statistics */}
              {statistics?.revenue && (
                <div className="bg-white rounded-lg shadow p-6">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">Revenue Overview</h3>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="text-center p-4 bg-green-50 rounded-lg">
                      <p className="text-sm text-gray-600 mb-1">Total Revenue</p>
                      <p className="text-2xl font-bold text-green-600">
                        ${(statistics.revenue.total || 0).toFixed(2)}
                      </p>
                    </div>
                    <div className="text-center p-4 bg-blue-50 rounded-lg">
                      <p className="text-sm text-gray-600 mb-1">This Month</p>
                      <p className="text-2xl font-bold text-blue-600">
                        ${(statistics.revenue.this_month || 0).toFixed(2)}
                      </p>
                    </div>
                    <div className="text-center p-4 bg-purple-50 rounded-lg">
                      <p className="text-sm text-gray-600 mb-1">Last Month</p>
                      <p className="text-2xl font-bold text-purple-600">
                        ${(statistics.revenue.last_month || 0).toFixed(2)}
                      </p>
                    </div>
                  </div>
                </div>
              )}

              {/* Quick Actions */}
              <div className="bg-white rounded-lg shadow p-6">
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <QuickActionButton
                    label="Manage Users"
                    icon="👥"
                    onClick={() => setActiveTab('users')}
                  />
                  <QuickActionButton
                    label="Manage Products"
                    icon="📦"
                    onClick={() => setActiveTab('products')}
                  />
                  <QuickActionButton
                    label="View Orders"
                    icon="🛒"
                    onClick={() => setActiveTab('orders')}
                  />
                  <QuickActionButton
                    label="System Settings"
                    icon="⚙️"
                    onClick={() => setActiveTab('settings')}
                  />
                </div>
              </div>

              {/* Recent Activity */}
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-lg shadow p-6">
                  <h2 className="text-lg font-semibold text-gray-900 mb-4">Recent Users</h2>
                  <div className="space-y-3">
                    {data?.recent_users && data.recent_users.length > 0 ? (
                      data.recent_users.map((user: any) => (
                        <div key={user.id} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                          <div>
                            <p className="font-medium text-gray-900">{user.name}</p>
                            <p className="text-sm text-gray-500">{user.email}</p>
                          </div>
                          <span className="text-xs text-gray-400">
                            {new Date(user.created_at).toLocaleDateString()}
                          </span>
                        </div>
                      ))
                    ) : (
                      <p className="text-gray-500 text-sm">No recent users</p>
                    )}
                  </div>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                  <h2 className="text-lg font-semibold text-gray-900 mb-4">Recent Orders</h2>
                  <div className="space-y-3">
                    {data?.recent_orders && data.recent_orders.length > 0 ? (
                      data.recent_orders.map((order: any) => (
                        <div key={order.id} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                          <div>
                            <p className="font-medium text-gray-900">Order #{order.id}</p>
                            <p className="text-sm text-gray-500">${order.total}</p>
                          </div>
                          <span className="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded">
                            {order.status}
                          </span>
                        </div>
                      ))
                    ) : (
                      <p className="text-gray-500 text-sm">No recent orders</p>
                    )}
                  </div>
                </div>
              </div>

              {/* System Status */}
              <div className="bg-white rounded-lg shadow p-6">
                <h2 className="text-lg font-semibold text-gray-900 mb-4">System Status</h2>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <StatusIndicator label="API" status="online" />
                  <StatusIndicator label="Database" status="online" />
                  <StatusIndicator label="Storage" status="online" />
                  <StatusIndicator label="Mail Service" status="warning" />
                </div>
              </div>
            </div>
          )}

          {activeTab === 'users' && <UsersManagement />}
          {activeTab === 'products' && <ProductsManagement />}
          {activeTab === 'orders' && <OrdersManagement />}
          {activeTab === 'categories' && <CategoriesManagement />}
          {activeTab === 'analytics' && <Analytics />}
          {activeTab === 'settings' && <SettingsManagement />}
          {activeTab === 'logs' && <SystemLogs />}
        </div>
      </div>
    </>
  );
}

function StatCard({ title, value, icon, color }: { title: string; value: string | number; icon: string; color: string }) {
  const colorClasses = {
    blue: 'bg-blue-100 text-blue-600',
    green: 'bg-green-100 text-green-600',
    purple: 'bg-purple-100 text-purple-600',
    yellow: 'bg-yellow-100 text-yellow-600',
  }[color] || 'bg-gray-100 text-gray-600';

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-600">{title}</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{value}</p>
        </div>
        <div className={`${colorClasses} rounded-full p-3 text-2xl`}>
          {icon}
        </div>
      </div>
    </div>
  );
}

function QuickActionButton({ label, icon, onClick }: { label: string; icon: string; onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className="flex flex-col items-center justify-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
    >
      <span className="text-2xl mb-2">{icon}</span>
      <span className="text-sm font-medium text-gray-700">{label}</span>
    </button>
  );
}

function StatusIndicator({ label, status }: { label: string; status: 'online' | 'offline' | 'warning' }) {
  const statusConfig = {
    online: { color: 'bg-green-100 text-green-800', text: 'Online' },
    offline: { color: 'bg-red-100 text-red-800', text: 'Offline' },
    warning: { color: 'bg-yellow-100 text-yellow-800', text: 'Warning' },
  };

  const config = statusConfig[status];

  return (
    <div className="flex flex-col items-center p-3 bg-gray-50 rounded">
      <span className="text-sm font-medium text-gray-700 mb-1">{label}</span>
      <span className={`px-2 py-1 text-xs rounded ${config.color}`}>{config.text}</span>
    </div>
  );
}
