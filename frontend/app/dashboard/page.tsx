'use client';

import React from 'react';
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

const RolePermissionManager = dynamic(() => import('../../components/RolePermissionManager'), { ssr: false });
const stats = [
  { name: 'Total Orders', value: '12', icon: ShoppingBagIcon, change: '+2 this week' },
  { name: 'Favorites', value: '24', icon: HeartIcon, change: '+4 new' },
  { name: 'Profile Views', value: '89', icon: UserIcon, change: '+12 this month' },
  { name: 'Messages', value: '3', icon: BellIcon, change: '2 unread' },
];

const recentOrders = [
  {
    id: 1,
    title: 'Wireless Headphones',
    price: '$129.99',
    status: 'Delivered',
    date: '2024-01-15',
    image: '/images/headphones.jpg'
  },
  {
    id: 2,
    title: 'Vintage Camera',
    price: '$299.99',
    status: 'In Transit',
    date: '2024-01-12',
    image: '/images/camera.jpg'
  },
  {
    id: 3,
    title: 'Gaming Keyboard',
    price: '$89.99',
    status: 'Processing',
    date: '2024-01-10',
    image: '/images/keyboard.jpg'
  },
];

function DashboardContent() {
  const { user, logout } = useAuth();

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Delivered':
        return 'bg-green-100 text-green-800';
      case 'In Transit':
        return 'bg-blue-100 text-blue-800';
      case 'Processing':
        return 'bg-yellow-100 text-yellow-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <>
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
                >
                  Logout
                </button>
              </div>
            </motion.div>
          </div>
          {/* Role/Permission Management (Admin only) */}
          <RolePermissionManager />

          {/* Stats Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {stats.map((stat, index) => (
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
                  <div className="space-y-4">
                    {recentOrders.map((order) => (
                      <div key={order.id} className="flex items-center space-x-4 p-4 hover:bg-gray-50 rounded-lg transition-colors">
                        <div className="w-12 h-12 bg-gray-200 rounded-lg flex-shrink-0">
                          {/* Placeholder for order image */}
                          <div className="w-full h-full bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center">
                            <ShoppingBagIcon className="h-6 w-6 text-white" />
                          </div>
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="font-medium text-gray-900 truncate">{order.title}</p>
                          <p className="text-sm text-gray-600">{order.date}</p>
                        </div>
                        <div className="text-right">
                          <p className="font-semibold text-gray-900">{order.price}</p>
                          <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(order.status)}`}>
                            {order.status}
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
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
    </>
  );
}

export default function DashboardPage() {
  return (
    <ProtectedRoute>
      <DashboardContent />
    </ProtectedRoute>
  );
}
