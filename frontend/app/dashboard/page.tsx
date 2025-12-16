'use client';

import React, { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import {
  ShoppingBagIcon,
  HeartIcon,
  CogIcon,
  ChartBarIcon,
  UserIcon,
  BellIcon,
} from '@heroicons/react/24/outline';
import { useAuth } from '../../hooks/useAuth';
import Header from '../../components/Header';
import ProtectedRoute from '../../components/ProtectedRoute';
import dynamic from 'next/dynamic';
import api from '../../lib/api';

const RolePermissionManager = dynamic(() => import('../../components/RolePermissionManager'), { ssr: false });
interface DashboardStats {
  total_orders: number;
  total_wishlist_items: number;
  total_reviews: number;
  unread_notifications: number;
}

interface Order {
  id: number;
  order_number: string;
  total_amount: number;
  status: string;
  created_at: string;
  items: Array<{
    product: {
      name: string;
      images?: string[];
    };
  }>;
}

function DashboardContent() {
  const { user, logout } = useAuth();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [recentOrders, setRecentOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        setLoading(true);
        const [statsRes, ordersRes] = await Promise.all([
          api.get('/dashboard/stats').catch(() => ({ data: { 
            total_orders: 0, 
            total_wishlist_items: 0, 
            total_reviews: 0, 
            unread_notifications: 0 
          }})),
          api.get('/orders?limit=3').catch(() => ({ data: { data: [] }}))
        ]);
        
        setStats(statsRes.data);
        setRecentOrders(ordersRes.data.data || []);
      } catch (error) {
        console.error('Failed to fetch dashboard data:', error);
      } finally {
        setLoading(false);
      }
    };

    if (user) {
      fetchDashboardData();
    }
  }, [user]);

  const getStatusColor = (status: string) => {
    const lowerStatus = status.toLowerCase();
    if (lowerStatus.includes('delivered') || lowerStatus.includes('completed')) {
      return 'bg-green-100 text-green-800';
    }
    if (lowerStatus.includes('transit') || lowerStatus.includes('shipped')) {
      return 'bg-blue-100 text-blue-800';
    }
    if (lowerStatus.includes('processing') || lowerStatus.includes('pending')) {
      return 'bg-yellow-100 text-yellow-800';
    }
    if (lowerStatus.includes('cancel')) {
      return 'bg-red-100 text-red-800';
    }
    return 'bg-gray-100 text-gray-800';
  };

  const statsDisplay = [
    { name: 'Total Orders', value: stats?.total_orders?.toString() || '0', icon: ShoppingBagIcon, change: 'All time' },
    { name: 'Wishlist Items', value: stats?.total_wishlist_items?.toString() || '0', icon: HeartIcon, change: 'Saved items' },
    { name: 'Reviews Given', value: stats?.total_reviews?.toString() || '0', icon: UserIcon, change: 'Your feedback' },
    { name: 'Notifications', value: stats?.unread_notifications?.toString() || '0', icon: BellIcon, change: 'Unread' },
  ];

  return (
    <div>
      <Header />
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
          {/* Header */}
          <div className="mb-8">
            <motion.div
              initial={{ opacity: 0, y: -20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5 }}
              className="flex items-center justify-between"
            >
              <div>
                <h1 className="text-3xl font-bold text-gray-900">
                  Welcome back, {user?.name}!
                </h1>
                <p className="text-gray-600 mt-1">
                  Here's what's happening with your account
                </p>
              </div>
              <div className="flex items-center space-x-4">
                <button className="btn-secondary">
                  <CogIcon className="h-5 w-5 mr-2" />
                  Settings
                </button>
                <button 
                  onClick={logout}
                  className="btn-ghost"
             loading ? (
              // Loading skeleton
              Array.from({ length: 4 }).map((_, index) => (
                <div key={index} className="bg-white rounded-2xl p-6 shadow-sm animate-pulse">
                  <div className="flex items-center">
                    <div className="w-10 h-10 bg-gray-200 rounded-lg"></div>
                    <div className="ml-4 flex-1">
                      <div className="h-4 bg-gray-200 rounded w-20 mb-2"></div>
                      <div className="h-6 bg-gray-200 rounded w-12"></div>
                    </div>
                  </div>
                  <div className="mt-4">
                    <div className="h-3 bg-gray-200 rounded w-16"></div>
                  </div>
                </div>
              ))
            ) : (
              statsDisplay.map((stat, index) => (
                <motion.div
                  key={stat.name}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                  className="bg-white rounded-2xl p-6 shadow-sm hover:shadow-lg transition-shadow"
                >
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <div className="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                        <stat.icon className="h-6 w-6 text-primary-600" />
                      </div>
                    </div>
                    <div className="ml-4">
                      <p className="text-sm font-medium text-gray-600">{stat.name}</p>
                      <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                    </div>
                  </div>
                  <div className="mt-4">
                    <p className="text-xs text-gray-500">{stat.change}</p>
                  </div>
                </motion.div>
              ))
                   </div>
                  </div>
                  <div className="ml-4">
                    <p className="text-sm font-medium text-gray-600">{stat.name}</p>
                    <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                  </div>
                </div>
                <div className="mt-4">
                  <p className="text-xs text-gray-500">{stat.change}</p>
                </div>
              </motion.div>
            ))}
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Recent Orders */}
            <div className="lg:col-span-2">
              <motion.div
                initial={{ opacity: 0, x: -20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ duration: 0.5, delay: 0.2 }}
                className="bg-white rounded-2xl shadow-sm"
              >
                <div className="p-6 border-b border-gray-200">
                  <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-gray-900">Recent Orders</h2>
                    <button className="text-primary-600 hover:text-primary-700 font-medium">
                      View all
                    </button>
                  </div>
                </div>
                <div className="p-6">
                  {loading ? (
                    // Loading skeleton
                    <div className="space-y-4">
                      {Array.from({ length: 3 }).map((_, i) => (
                        <div key={i} className="flex items-center space-x-4 p-4 animate-pulse">
                          <div className="w-12 h-12 bg-gray-200 rounded-lg flex-shrink-0"></div>
                          <div className="flex-1">
                            <div className="h-4 bg-gray-200 rounded w-32 mb-2"></div>
                            <div className="h-3 bg-gray-200 rounded w-20"></div>
                          </div>
                          <div className="text-right">
                            <div className="h-4 bg-gray-200 rounded w-16 mb-2 ml-auto"></div>
                            <div className="h-5 bg-gray-200 rounded w-20"></div>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : recentOrders.length > 0 ? (
                    <div className="space-y-4">
                      {recentOrders.map((order) => (
                        <div key={order.id} className="flex items-center space-x-4 p-4 hover:bg-gray-50 rounded-lg transition-colors">
                          <div className="w-12 h-12 bg-gray-200 rounded-lg flex-shrink-0">
                            <div className="w-full h-full bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center">
                              <ShoppingBagIcon className="h-6 w-6 text-white" />
                            </div>
                          </div>
                          <div className="flex-1 min-w-0">
                            <p className="font-medium text-gray-900 truncate">
                              {order.items?.[0]?.product?.name || `Order #${order.order_number}`}
                            </p>
                            <p className="text-sm text-gray-600">
                              {new Date(order.created_at).toLocaleDateString()}
                            </p>
                          </div>
                          <div className="text-right">
                            <p className="font-semibold text-gray-900">
                              ${order.total_amount.toFixed(2)}
                            </p>
                            <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(order.status)}`}>
                              {order.status}
                            </span>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8">
                      <ShoppingBagIcon className="h-12 w-12 text-gray-400 mx-auto mb-3" />
                      <p className="text-gray-500">No orders yet</p>
                      <button className="mt-4 btn-primary">
                        Start Shopping
                      </button>
                    </div>
                  )}
                </div>
              </motion.div>
            </div>

            {/* Quick Actions */}
            <div className="space-y-6">
              <motion.div
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ duration: 0.5, delay: 0.3 }}
                className="bg-white rounded-2xl p-6 shadow-sm"
              >
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div className="space-y-3">
                  <button className="w-full btn-primary text-left justify-start">
                    <ShoppingBagIcon className="h-5 w-5 mr-2" />
                    Browse Marketplace
                  </button>
                  <button className="w-full btn-secondary text-left justify-start">
                    <ChartBarIcon className="h-5 w-5 mr-2" />
                    Sell an Item
                  </button>
                  <button className="w-full btn-ghost text-left justify-start">
                    <HeartIcon className="h-5 w-5 mr-2" />
                    View Favorites
                  </button>
                </div>
              </motion.div>

              {/* Account Info */}
              <motion.div
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ duration: 0.5, delay: 0.4 }}
                className="bg-white rounded-2xl p-6 shadow-sm"
              >
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Account Info</h3>
                <div className="space-y-3">
                  <div className="flex items-center">
                    <UserIcon className="h-5 w-5 text-gray-400 mr-3" />
                    <div>
                      <p className="text-sm font-medium text-gray-900">{user?.name}</p>
                      <p className="text-xs text-gray-500">Full Name</p>
                    </div>
                  </div>
                  <div className="flex items-center">
                    <svg className="h-5 w-5 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                    <div>
                      <p className="text-sm font-medium text-gray-900">{user?.email}</p>
                      <p className="text-xs text-gray-500">Email Address</p>
                    </div>
                  </div>
                  <div className="flex items-center">
                    <svg className="h-5 w-5 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3a4 4 0 118 0v4m-4 6v6m-1 0h2m-1 0h2" />
                    </svg>
                    <div>
                      <p className="text-sm font-medium text-green-600">
                        {user?.email_verified_at ? 'Verified' : 'Not Verified'}
                      </p>
                      <p className="text-xs text-gray-500">Account Status</p>
                    </div>
                  </div>
                </div>
              </motion.div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default function DashboardPage() {
  return (
    <ProtectedRoute>
      <DashboardContent />
    </ProtectedRoute>
  );
}
