'use client';

import React, { useState } from 'react';
import { motion } from 'framer-motion';
import {
  ShoppingBagIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  EyeIcon,
} from '@heroicons/react/24/outline';
import { useAuth } from '@/hooks/useAuth';
import Header from '@/components/Header';
import ProtectedRoute from '@/components/ProtectedRoute';
import Link from 'next/link';

// Mock data - replace with API call
const mockOrders = [
  {
    id: 'ORD-2025-001',
    date: '2025-10-05',
    status: 'Delivered',
    total: 459.97,
    items: 3,
    products: [
      { name: 'Wireless Headphones', price: 129.99, quantity: 1, image: '/images/p1.jpg' },
      { name: 'USB-C Cable', price: 15.99, quantity: 2, image: '/images/p2.jpg' },
    ]
  },
  {
    id: 'ORD-2025-002',
    date: '2025-10-08',
    status: 'Processing',
    total: 299.99,
    items: 1,
    products: [
      { name: 'Laptop Stand', price: 299.99, quantity: 1, image: '/images/p3.jpg' },
    ]
  },
  {
    id: 'ORD-2025-003',
    date: '2025-10-09',
    status: 'Shipped',
    total: 89.98,
    items: 2,
    products: [
      { name: 'Phone Case', price: 24.99, quantity: 2, image: '/images/p4.jpg' },
    ]
  },
];

function OrdersContent() {
  const { user } = useAuth();
  const [filter, setFilter] = useState<'all' | 'processing' | 'shipped' | 'delivered'>('all');
  const [searchQuery, setSearchQuery] = useState('');

  const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
      case 'delivered':
        return 'bg-green-100 text-green-800 border-green-200';
      case 'shipped':
        return 'bg-blue-100 text-blue-800 border-blue-200';
      case 'processing':
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      case 'cancelled':
        return 'bg-red-100 text-red-800 border-red-200';
      default:
        return 'bg-gray-100 text-gray-800 border-gray-200';
    }
  };

  const filteredOrders = mockOrders.filter(order => {
    const matchesFilter = filter === 'all' || order.status.toLowerCase() === filter;
    const matchesSearch = order.id.toLowerCase().includes(searchQuery.toLowerCase()) ||
                         order.products.some(p => p.name.toLowerCase().includes(searchQuery.toLowerCase()));
    return matchesFilter && matchesSearch;
  });

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Page Header */}
          <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            className="mb-8"
          >
            <h1 className="text-3xl font-bold text-gray-900">My Orders</h1>
            <p className="text-gray-600 mt-1">Track and manage your purchases</p>
          </motion.div>

          {/* Filters and Search */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="bg-white rounded-lg shadow-md p-4 mb-6"
          >
            <div className="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
              {/* Search */}
              <div className="relative flex-1 max-w-md">
                <MagnifyingGlassIcon className="absolute left-3 top-3 h-5 w-5 text-gray-400" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search orders or products..."
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>

              {/* Filter Tabs */}
              <div className="flex items-center space-x-2">
                <FunnelIcon className="h-5 w-5 text-gray-400" />
                <div className="flex space-x-2">
                  {['all', 'processing', 'shipped', 'delivered'].map((status) => (
                    <button
                      key={status}
                      onClick={() => setFilter(status as any)}
                      className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                        filter === status
                          ? 'bg-primary-600 text-white'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                      }`}
                    >
                      {status.charAt(0).toUpperCase() + status.slice(1)}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </motion.div>

          {/* Orders List */}
          <div className="space-y-4">
            {filteredOrders.length === 0 ? (
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                className="bg-white rounded-lg shadow-md p-12 text-center"
              >
                <ShoppingBagIcon className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                <p className="text-gray-600 mb-6">
                  {searchQuery ? 'Try a different search term' : 'Start shopping to see your orders here'}
                </p>
                <Link href="/marketplace" className="btn-primary px-6 py-2 inline-block">
                  Browse Products
                </Link>
              </motion.div>
            ) : (
              filteredOrders.map((order, index) => (
                <motion.div
                  key={order.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: index * 0.1 }}
                  className="bg-white rounded-lg shadow-md overflow-hidden"
                >
                  {/* Order Header */}
                  <div className="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between">
                      <div className="flex items-center space-x-4">
                        <div>
                          <p className="text-sm text-gray-600">Order ID</p>
                          <p className="font-semibold text-gray-900">{order.id}</p>
                        </div>
                        <div>
                          <p className="text-sm text-gray-600">Date</p>
                          <p className="font-medium text-gray-900">
                            {new Date(order.date).toLocaleDateString('en-US', { 
                              year: 'numeric', 
                              month: 'short', 
                              day: 'numeric' 
                            })}
                          </p>
                        </div>
                        <div>
                          <p className="text-sm text-gray-600">Total</p>
                          <p className="font-semibold text-gray-900">
                            ZMW {order.total.toFixed(2)}
                          </p>
                        </div>
                      </div>
                      
                      <div className="mt-4 md:mt-0 flex items-center space-x-4">
                        <span className={`px-4 py-2 rounded-full text-sm font-medium border ${getStatusColor(order.status)}`}>
                          {order.status}
                        </span>
                        <Link
                          href={`/orders/${order.id}`}
                          className="flex items-center space-x-2 text-primary-600 hover:text-primary-700 font-medium"
                        >
                          <EyeIcon className="h-5 w-5" />
                          <span>View Details</span>
                        </Link>
                      </div>
                    </div>
                  </div>

                  {/* Order Items */}
                  <div className="px-6 py-4">
                    <div className="space-y-4">
                      {order.products.map((product, idx) => (
                        <div key={idx} className="flex items-center space-x-4">
                          <div className="h-16 w-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                            <ShoppingBagIcon className="h-8 w-8 text-gray-400" />
                          </div>
                          <div className="flex-1">
                            <h4 className="font-medium text-gray-900">{product.name}</h4>
                            <p className="text-sm text-gray-600">Quantity: {product.quantity}</p>
                          </div>
                          <div className="text-right">
                            <p className="font-semibold text-gray-900">
                              ZMW {product.price.toFixed(2)}
                            </p>
                          </div>
                        </div>
                      ))}
                    </div>

                    {/* Order Actions */}
                    <div className="mt-4 pt-4 border-t border-gray-200 flex items-center justify-end space-x-4">
                      <Link
                        href={`/orders/${order.id}/tracking`}
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
                      >
                        Track Order
                      </Link>
                      {order.status === 'Delivered' && (
                        <button className="text-primary-600 hover:text-primary-700 font-medium text-sm">
                          Leave Review
                        </button>
                      )}
                      {(order.status === 'Processing' || order.status === 'Shipped') && (
                        <button className="text-red-600 hover:text-red-700 font-medium text-sm">
                          Cancel Order
                        </button>
                      )}
                      <button className="text-gray-600 hover:text-gray-700 font-medium text-sm">
                        Contact Seller
                      </button>
                    </div>
                  </div>
                </motion.div>
              ))
            )}
          </div>

          {/* Order Stats */}
          {filteredOrders.length > 0 && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ delay: 0.3 }}
              className="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4"
            >
              <div className="bg-white rounded-lg shadow-md p-4 text-center">
                <p className="text-2xl font-bold text-gray-900">{mockOrders.length}</p>
                <p className="text-sm text-gray-600">Total Orders</p>
              </div>
              <div className="bg-white rounded-lg shadow-md p-4 text-center">
                <p className="text-2xl font-bold text-green-600">
                  {mockOrders.filter(o => o.status === 'Delivered').length}
                </p>
                <p className="text-sm text-gray-600">Delivered</p>
              </div>
              <div className="bg-white rounded-lg shadow-md p-4 text-center">
                <p className="text-2xl font-bold text-blue-600">
                  {mockOrders.filter(o => o.status === 'Shipped').length}
                </p>
                <p className="text-sm text-gray-600">Shipped</p>
              </div>
              <div className="bg-white rounded-lg shadow-md p-4 text-center">
                <p className="text-2xl font-bold text-yellow-600">
                  {mockOrders.filter(o => o.status === 'Processing').length}
                </p>
                <p className="text-sm text-gray-600">Processing</p>
              </div>
            </motion.div>
          )}
        </div>
      </div>
    </>
  );
}

export default function OrdersPage() {
  return (
    <ProtectedRoute>
      <OrdersContent />
    </ProtectedRoute>
  );
}
