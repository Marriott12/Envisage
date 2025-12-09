'use client';

import { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { motion } from 'framer-motion';
import {
  CheckCircleIcon,
  TruckIcon,
  ClockIcon,
  MapPinIcon,
  PhoneIcon,
  EnvelopeIcon,
  ArrowLeftIcon,
  DocumentArrowDownIcon,
  ChatBubbleLeftRightIcon,
} from '@heroicons/react/24/outline';
import { CheckCircleIcon as CheckCircleSolid } from '@heroicons/react/24/solid';
import Header from '@/components/Header';
import ProtectedRoute from '@/components/ProtectedRoute';
import axios from 'axios';
import { toast } from 'react-hot-toast';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'https://envisagezm.com/api';

interface OrderItem {
  id: number;
  product_id: number;
  product_name: string;
  quantity: number;
  price: number;
  image?: string;
}

interface Order {
  id: number;
  order_number: string;
  status: string;
  total: number;
  currency: string;
  shipping_address: string;
  tracking_number?: string;
  estimated_delivery?: string;
  notes?: string;
  created_at: string;
  updated_at: string;
  items: OrderItem[];
  status_history?: Array<{
    status: string;
    timestamp: string;
    notes?: string;
  }>;
}

const ORDER_STATUSES = [
  { key: 'pending', label: 'Order Placed', icon: CheckCircleIcon },
  { key: 'processing', label: 'Processing', icon: ClockIcon },
  { key: 'shipped', label: 'Shipped', icon: TruckIcon },
  { key: 'delivered', label: 'Delivered', icon: CheckCircleSolid },
];

function OrderTrackingContent() {
  const params = useParams();
  const router = useRouter();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const orderId = params?.id as string;

  useEffect(() => {
    if (orderId) {
      fetchOrderDetails();
    }
  }, [orderId]);

  const fetchOrderDetails = async () => {
    try {
      setLoading(true);
      setError(null);
      const token = localStorage.getItem('token');
      
      const response = await axios.get(`${API_BASE_URL}/orders/${orderId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (response.data.status === 'success') {
        setOrder(response.data.data.order || response.data.data);
      } else {
        setError(response.data.message || 'Failed to load order');
      }
    } catch (err: any) {
      console.error('Error fetching order:', err);
      setError(err.response?.data?.message || 'Failed to load order details');
      toast.error('Failed to load order details');
    } finally {
      setLoading(false);
    }
  };

  const getCurrentStatusIndex = () => {
    if (!order) return 0;
    const statusKey = order.status.toLowerCase();
    const index = ORDER_STATUSES.findIndex(s => s.key === statusKey);
    return index >= 0 ? index : 0;
  };

  const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
      case 'delivered':
        return 'bg-green-500';
      case 'shipped':
        return 'bg-blue-500';
      case 'processing':
        return 'bg-yellow-500';
      case 'pending':
        return 'bg-gray-400';
      case 'cancelled':
        return 'bg-red-500';
      default:
        return 'bg-gray-400';
    }
  };

  const handleDownloadInvoice = async () => {
    toast.success('Invoice download will be implemented');
    // TODO: Implement invoice download
  };

  const handleContactSeller = () => {
    toast.success('Messaging will be implemented');
    // TODO: Implement messaging
  };

  if (loading) {
    return (
      <>
        <Header />
        <div className="min-h-screen bg-gray-50 flex items-center justify-center">
          <div className="text-center">
            <div className="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-blue-600 border-r-transparent"></div>
            <p className="mt-4 text-gray-600">Loading order details...</p>
          </div>
        </div>
      </>
    );
  }

  if (error || !order) {
    return (
      <>
        <Header />
        <div className="min-h-screen bg-gray-50 flex items-center justify-center">
          <div className="text-center">
            <h2 className="text-2xl font-bold text-gray-900 mb-2">Order Not Found</h2>
            <p className="text-gray-600 mb-6">{error || 'Unable to load order details'}</p>
            <button
              onClick={() => router.push('/orders')}
              className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Back to Orders
            </button>
          </div>
        </div>
      </>
    );
  }

  const currentStatusIndex = getCurrentStatusIndex();

  return (
    <>
      <Header />
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Back Button */}
          <motion.button
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            onClick={() => router.push('/orders')}
            className="flex items-center gap-2 text-blue-600 hover:text-blue-700 mb-6"
          >
            <ArrowLeftIcon className="h-5 w-5" />
            Back to Orders
          </motion.button>

          {/* Header */}
          <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            className="mb-8"
          >
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Order Tracking</h1>
            <p className="text-gray-600">Order #{order.order_number}</p>
          </motion.div>

          <div className="grid lg:grid-cols-3 gap-6">
            {/* Main Content */}
            <div className="lg:col-span-2 space-y-6">
              {/* Status Timeline */}
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.1 }}
                className="bg-white rounded-lg shadow-md p-6"
              >
                <h2 className="text-xl font-bold text-gray-900 mb-6">Delivery Status</h2>

                <div className="relative">
                  {ORDER_STATUSES.map((status, index) => {
                    const isCompleted = index <= currentStatusIndex;
                    const isCurrent = index === currentStatusIndex;
                    const StatusIcon = status.icon;

                    return (
                      <div key={status.key} className="relative pb-8 last:pb-0">
                        {index < ORDER_STATUSES.length - 1 && (
                          <div
                            className={`absolute left-5 top-10 h-full w-0.5 ${
                              isCompleted ? 'bg-blue-600' : 'bg-gray-300'
                            }`}
                          />
                        )}

                        <div className="flex items-start gap-4">
                          <div
                            className={`flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center ${
                              isCompleted
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-200 text-gray-400'
                            } ${isCurrent ? 'ring-4 ring-blue-100' : ''}`}
                          >
                            <StatusIcon className="h-5 w-5" />
                          </div>

                          <div className="flex-1 pt-1">
                            <h3
                              className={`text-lg font-semibold ${
                                isCompleted ? 'text-gray-900' : 'text-gray-400'
                              }`}
                            >
                              {status.label}
                            </h3>
                            {isCurrent && (
                              <p className="text-sm text-blue-600 mt-1">
                                {order.status === 'delivered'
                                  ? `Delivered on ${new Date(order.updated_at).toLocaleDateString()}`
                                  : order.status === 'shipped' && order.estimated_delivery
                                  ? `Estimated delivery: ${new Date(order.estimated_delivery).toLocaleDateString()}`
                                  : 'In progress'}
                              </p>
                            )}
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>

                {order.status.toLowerCase() === 'cancelled' && (
                  <div className="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p className="text-red-800 font-semibold">Order Cancelled</p>
                    {order.notes && <p className="text-red-700 text-sm mt-1">{order.notes}</p>}
                  </div>
                )}
              </motion.div>

              {/* Tracking Information */}
              {order.tracking_number && (
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: 0.2 }}
                  className="bg-white rounded-lg shadow-md p-6"
                >
                  <h2 className="text-xl font-bold text-gray-900 mb-4">Tracking Information</h2>
                  
                  <div className="space-y-4">
                    <div className="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                      <TruckIcon className="h-6 w-6 text-blue-600" />
                      <div>
                        <p className="text-sm text-gray-600">Tracking Number</p>
                        <p className="font-mono font-semibold text-gray-900">{order.tracking_number}</p>
                      </div>
                    </div>

                    {order.estimated_delivery && (
                      <div className="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                        <ClockIcon className="h-6 w-6 text-blue-600" />
                        <div>
                          <p className="text-sm text-gray-600">Estimated Delivery</p>
                          <p className="font-semibold text-gray-900">
                            {new Date(order.estimated_delivery).toLocaleDateString('en-US', {
                              weekday: 'long',
                              year: 'numeric',
                              month: 'long',
                              day: 'numeric',
                            })}
                          </p>
                        </div>
                      </div>
                    )}

                    <div className="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
                      <MapPinIcon className="h-6 w-6 text-blue-600 flex-shrink-0" />
                      <div>
                        <p className="text-sm text-gray-600">Delivery Address</p>
                        <p className="font-semibold text-gray-900 whitespace-pre-wrap">
                          {order.shipping_address}
                        </p>
                      </div>
                    </div>
                  </div>
                </motion.div>
              )}

              {/* Order Items */}
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.3 }}
                className="bg-white rounded-lg shadow-md p-6"
              >
                <h2 className="text-xl font-bold text-gray-900 mb-4">Order Items</h2>
                
                <div className="space-y-4">
                  {order.items.map((item) => (
                    <div key={item.id} className="flex gap-4 p-4 border border-gray-200 rounded-lg">
                      {item.image && (
                        <img
                          src={item.image}
                          alt={item.product_name}
                          className="h-20 w-20 object-cover rounded-lg"
                        />
                      )}
                      <div className="flex-1">
                        <h3 className="font-semibold text-gray-900">{item.product_name}</h3>
                        <p className="text-sm text-gray-600 mt-1">Quantity: {item.quantity}</p>
                        <p className="text-blue-600 font-semibold mt-2">
                          {order.currency} {(item.price * item.quantity).toFixed(2)}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              </motion.div>
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              {/* Order Summary */}
              <motion.div
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: 0.2 }}
                className="bg-white rounded-lg shadow-md p-6"
              >
                <h2 className="text-lg font-bold text-gray-900 mb-4">Order Summary</h2>
                
                <div className="space-y-3">
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-600">Order Date</span>
                    <span className="font-semibold">
                      {new Date(order.created_at).toLocaleDateString()}
                    </span>
                  </div>
                  
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-600">Status</span>
                    <span
                      className={`px-3 py-1 rounded-full text-xs font-semibold ${
                        order.status.toLowerCase() === 'delivered'
                          ? 'bg-green-100 text-green-800'
                          : order.status.toLowerCase() === 'shipped'
                          ? 'bg-blue-100 text-blue-800'
                          : order.status.toLowerCase() === 'processing'
                          ? 'bg-yellow-100 text-yellow-800'
                          : 'bg-gray-100 text-gray-800'
                      }`}
                    >
                      {order.status}
                    </span>
                  </div>

                  <div className="border-t border-gray-200 pt-3 mt-3">
                    <div className="flex justify-between">
                      <span className="font-semibold text-gray-900">Total</span>
                      <span className="font-bold text-xl text-blue-600">
                        {order.currency} {order.total.toFixed(2)}
                      </span>
                    </div>
                  </div>
                </div>
              </motion.div>

              {/* Actions */}
              <motion.div
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: 0.3 }}
                className="bg-white rounded-lg shadow-md p-6"
              >
                <h2 className="text-lg font-bold text-gray-900 mb-4">Actions</h2>
                
                <div className="space-y-3">
                  <button
                    onClick={handleDownloadInvoice}
                    className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                  >
                    <DocumentArrowDownIcon className="h-5 w-5" />
                    Download Invoice
                  </button>

                  <button
                    onClick={handleContactSeller}
                    className="w-full flex items-center justify-center gap-2 px-4 py-3 border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors"
                  >
                    <ChatBubbleLeftRightIcon className="h-5 w-5" />
                    Contact Seller
                  </button>

                  {order.status.toLowerCase() === 'delivered' && (
                    <button
                      onClick={() => router.push(`/marketplace/${order.items[0]?.product_id}`)}
                      className="w-full px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      Write a Review
                    </button>
                  )}
                </div>
              </motion.div>

              {/* Help */}
              <motion.div
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: 0.4 }}
                className="bg-blue-50 rounded-lg p-6"
              >
                <h3 className="font-semibold text-gray-900 mb-3">Need Help?</h3>
                <p className="text-sm text-gray-700 mb-4">
                  Our customer support team is here to help you with any questions.
                </p>
                <div className="space-y-2 text-sm">
                  <div className="flex items-center gap-2 text-gray-700">
                    <PhoneIcon className="h-4 w-4" />
                    <span>+260 XXX XXX XXX</span>
                  </div>
                  <div className="flex items-center gap-2 text-gray-700">
                    <EnvelopeIcon className="h-4 w-4" />
                    <span>support@envisagezm.com</span>
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

export default function OrderTrackingPage() {
  return (
    <ProtectedRoute>
      <OrderTrackingContent />
    </ProtectedRoute>
  );
}
